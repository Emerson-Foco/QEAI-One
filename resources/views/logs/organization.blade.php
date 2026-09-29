@extends('layouts.panel')

@section('title', 'Logs — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <a class="muted" href="{{ route('panel.organizations.show', $organization) }}">← {{ $organization->name }}</a>
    <h1>Logs da organização</h1>
    <p class="muted">Ações realizadas nesta organização (escopo organização), com hash encadeado.</p>
  </div>
</div>

<div class="card">
  <form method="get" class="grid-2">
    <label>Ação contém<input name="action" value="{{ $filters['action'] ?? '' }}"></label>
    <label>De<input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
    <label>Até<input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
    <div class="full btn-row">
      <button class="btn secondary">Filtrar</button>
      <a class="btn secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">Exportar CSV</a>
      <a class="btn secondary" href="{{ route('panel.organizations.logs', $organization) }}">Limpar</a>
    </div>
  </form>
</div>

<div class="card">
  <table class="table">
    <thead><tr><th>Quando</th><th>Ator</th><th>Ação</th><th>Entidade</th><th>IP</th></tr></thead>
    <tbody>
      @forelse ($logs as $log)
        <tr>
          <td>{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
          <td>{{ $log->actor_type }}{{ $log->actor_user_id ? ' #' . $log->actor_user_id : '' }}</td>
          <td>{{ $log->action }}</td>
          <td>{{ $log->entity_type }}{{ $log->entity_id ? ' #' . $log->entity_id : '' }}</td>
          <td>{{ $log->ip }}</td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted">Sem registros para os filtros atuais.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if ($logs->hasPages())
    <nav class="pagination">
      @if ($logs->onFirstPage())<span class="pagination-link is-disabled">← Anterior</span>@else<a class="pagination-link" href="{{ $logs->previousPageUrl() }}">← Anterior</a>@endif
      <span class="pagination-status">Página {{ $logs->currentPage() }} de {{ $logs->lastPage() }}</span>
      @if ($logs->hasMorePages())<a class="pagination-link" href="{{ $logs->nextPageUrl() }}">Próxima →</a>@else<span class="pagination-link is-disabled">Próxima →</span>@endif
    </nav>
  @endif
</div>
@endsection
