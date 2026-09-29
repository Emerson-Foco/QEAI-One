@extends('layouts.member')

@section('title', 'Empresas — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <span class="pill">CRM</span>
    <h1>Empresas</h1>
    <p class="muted">Organizações clientes/fornecedores.</p>
  </div>
  <form method="get" class="inline-form">
    <input name="q" value="{{ $search }}" placeholder="Buscar por nome">
    <button class="btn secondary">Buscar</button>
  </form>
</div>

<div class="card">
  <h2>Nova empresa</h2>
  <form method="post" action="{{ route('member.org.companies.store', $organization) }}">
    @csrf
    <div class="grid-2">
      <label>Nome<input name="name" required maxlength="180"></label>
      <label>CNPJ / documento<input name="document" maxlength="24"></label>
      <label>Site<input name="website" maxlength="255"></label>
      <label>Telefone<input name="phone" maxlength="40"></label>
      <label class="full">Observações<textarea name="notes" rows="3" maxlength="5000"></textarea></label>
    </div>
    <button class="btn">Criar empresa</button>
  </form>
</div>

<div class="card">
  <table class="table">
    <thead><tr><th>Nome</th><th>Documento</th><th>Site</th><th>Contatos</th><th></th></tr></thead>
    <tbody>
      @forelse ($companies as $company)
        <tr>
          <td><strong>{{ $company->name }}</strong></td>
          <td>{{ $company->document ?: '—' }}</td>
          <td>{{ $company->website ?: '—' }}</td>
          <td>{{ $company->contacts_count }}</td>
          <td class="right">
            <div class="row-actions inline">
              <a class="btn secondary small" href="{{ route('member.org.companies.edit', [$organization, $company]) }}">Editar</a>
              <form method="post" action="{{ route('member.org.companies.destroy', [$organization, $company]) }}" onsubmit="return confirm('Remover empresa?');">
                @csrf @method('DELETE')
                <button class="btn secondary small">Excluir</button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted">Nenhuma empresa ainda.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if ($companies->hasPages())
    <nav class="pagination">
      @if ($companies->onFirstPage())<span class="pagination-link is-disabled">← Anterior</span>@else<a class="pagination-link" href="{{ $companies->previousPageUrl() }}">← Anterior</a>@endif
      <span class="pagination-status">Página {{ $companies->currentPage() }} de {{ $companies->lastPage() }}</span>
      @if ($companies->hasMorePages())<a class="pagination-link" href="{{ $companies->nextPageUrl() }}">Próxima →</a>@else<span class="pagination-link is-disabled">Próxima →</span>@endif
    </nav>
  @endif
</div>
@endsection
