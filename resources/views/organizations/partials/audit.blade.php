@php $logsRoute = $logsRoute ?? 'panel.organizations.logs'; @endphp
<div class="card">
  <h2 style="display:flex;justify-content:space-between;align-items:center">Auditoria da organização <a class="btn secondary" href="{{ route($logsRoute, $organization) }}">Ver todos os logs</a></h2>
  <table class="table">
    <thead><tr><th>Quando</th><th>Ação</th><th>Entidade</th><th>IP</th></tr></thead>
    <tbody>
      @forelse ($audit as $log)
        <tr>
          <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
          <td>{{ $log->action }}</td>
          <td>{{ $log->entity_type }}{{ $log->entity_id ? ' #' . $log->entity_id : '' }}</td>
          <td>{{ $log->ip }}</td>
        </tr>
      @empty
        <tr><td colspan="4" class="muted">Sem registros.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
