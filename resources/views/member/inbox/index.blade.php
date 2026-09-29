@extends('layouts.member')

@section('title', 'Atendimento — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <span class="pill">Atendimento</span>
    <h1>Caixa de entrada</h1>
  </div>
  <form method="get" class="inline-form">
    <select name="status" onchange="this.form.submit()" aria-label="Filtrar por status">
      <option value="">Todas</option>
      <option value="open" @selected($status === 'open')>Abertas</option>
      <option value="pending" @selected($status === 'pending')>Pendentes</option>
      <option value="closed" @selected($status === 'closed')>Fechadas</option>
    </select>
  </form>
</div>

<div class="card">
  <table class="table">
    <thead><tr><th>Contato</th><th>Canal</th><th>Status</th><th>Responsável</th><th>Última mensagem</th><th></th></tr></thead>
    <tbody>
      @forelse ($conversations as $conversation)
        <tr>
          <td><strong>{{ $conversation->contact?->name ?? 'Visitante' }}</strong>@if ($conversation->contact?->email)<br><small class="muted">{{ $conversation->contact->email }}</small>@endif</td>
          <td>{{ $conversation->channel?->name }} <small class="muted">({{ $conversation->channel?->type }})</small></td>
          <td><span class="badge">{{ $conversation->status }}</span></td>
          <td>{{ $conversation->assignee?->name ?? '—' }}</td>
          <td>{{ $conversation->last_message_at?->format('d/m H:i') ?? '—' }}</td>
          <td class="right"><a class="btn secondary small" href="{{ route('member.org.inbox.show', [$organization, $conversation]) }}">Abrir</a></td>
        </tr>
      @empty
        <tr><td colspan="6" class="muted">Nenhuma conversa.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if ($conversations->hasPages())
    <nav class="pagination">
      @if ($conversations->onFirstPage())<span class="pagination-link is-disabled">← Anterior</span>@else<a class="pagination-link" href="{{ $conversations->previousPageUrl() }}">← Anterior</a>@endif
      <span class="pagination-status">Página {{ $conversations->currentPage() }} de {{ $conversations->lastPage() }}</span>
      @if ($conversations->hasMorePages())<a class="pagination-link" href="{{ $conversations->nextPageUrl() }}">Próxima →</a>@else<span class="pagination-link is-disabled">Próxima →</span>@endif
    </nav>
  @endif
</div>
@endsection
