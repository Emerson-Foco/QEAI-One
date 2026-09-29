@extends('layouts.member')

@section('title', 'Contatos — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <span class="pill">CRM</span>
    <h1>Contatos</h1>
    <p class="muted">Pessoas desta organização.</p>
  </div>
  <form method="get" class="inline-form">
    <input name="q" value="{{ $search }}" placeholder="Buscar nome, e-mail ou telefone">
    <button class="btn secondary">Buscar</button>
  </form>
</div>

<div class="card">
  <h2>Novo contato</h2>
  <form method="post" action="{{ route('member.org.contacts.store', $organization) }}">
    @csrf
    <div class="grid-2">
      <label>Nome<input name="name" required maxlength="180"></label>
      <label>E-mail<input type="email" name="email" maxlength="180"></label>
      <label>Telefone<input name="phone" maxlength="40"></label>
      <label>WhatsApp<input name="whatsapp" maxlength="40"></label>
      <label>Cargo<input name="job_title" maxlength="120"></label>
      <label>Origem<input name="source" maxlength="60" placeholder="site, indicação, anúncio…"></label>
      <label>Empresa<select name="company_id"><option value="">—</option>@foreach ($companies as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach</select></label>
      <label>Tags (separadas por vírgula)<input name="tags" maxlength="300"></label>
      <label class="full">Observações<textarea name="notes" rows="3" maxlength="5000"></textarea></label>
    </div>
    <button class="btn">Criar contato</button>
  </form>
</div>

<div class="card">
  <table class="table">
    <thead><tr><th>Nome</th><th>Contato</th><th>Empresa</th><th>Tags</th><th></th></tr></thead>
    <tbody>
      @forelse ($contacts as $contact)
        <tr>
          <td><strong>{{ $contact->name }}</strong>@if ($contact->job_title)<br><small class="muted">{{ $contact->job_title }}</small>@endif</td>
          <td>{{ $contact->email ?: '—' }}<br><small class="muted">{{ $contact->phone ?: ($contact->whatsapp ?: '') }}</small></td>
          <td>{{ $contact->company?->name ?? '—' }}</td>
          <td>@foreach ($contact->tags as $tag)<span class="badge">{{ $tag->name }}</span> @endforeach</td>
          <td class="right">
            <div class="row-actions inline">
              <a class="btn secondary small" href="{{ route('member.org.contacts.edit', [$organization, $contact]) }}">Editar</a>
              <form method="post" action="{{ route('member.org.contacts.destroy', [$organization, $contact]) }}" onsubmit="return confirm('Remover contato?');">
                @csrf @method('DELETE')
                <button class="btn secondary small">Excluir</button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted">Nenhum contato ainda.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if ($contacts->hasPages())
    <nav class="pagination">
      @if ($contacts->onFirstPage())<span class="pagination-link is-disabled">← Anterior</span>@else<a class="pagination-link" href="{{ $contacts->previousPageUrl() }}">← Anterior</a>@endif
      <span class="pagination-status">Página {{ $contacts->currentPage() }} de {{ $contacts->lastPage() }}</span>
      @if ($contacts->hasMorePages())<a class="pagination-link" href="{{ $contacts->nextPageUrl() }}">Próxima →</a>@else<span class="pagination-link is-disabled">Próxima →</span>@endif
    </nav>
  @endif
</div>
@endsection
