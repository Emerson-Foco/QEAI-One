@extends('layouts.panel')

@section('title', $organization->name . ' — QEAI One')

@section('panel')
<div class="page-head">
  <div>
    <a class="muted" href="{{ route('panel.organizations.index') }}">← Organizações</a>
    <h1>{{ $organization->name }}</h1>
    <p class="muted">
      <span class="badge">{{ $organization->status }}</span>
      <span>·</span> {{ $organization->slug }}
      @if ($organization->document) <span>·</span> {{ $organization->document }} @endif
    </p>
  </div>
</div>

<div class="card">
  <h2>Plano</h2>
  <form method="post" action="{{ route('panel.organizations.plan', $organization) }}" class="inline-form">
    @csrf @method('PUT')
    <select name="plan_id" required>
      @foreach ($plans as $plan)
        <option value="{{ $plan->id }}" @selected($organization->plan_id === $plan->id)>{{ $plan->name }} — {{ $plan->priceFormatted() }}/{{ $plan->interval }}</option>
      @endforeach
    </select>
    <button class="btn secondary">Salvar plano</button>
  </form>
  @if ($organization->plan)
    <ul class="feature-list">
      @foreach ($planCatalog as $key => $meta)
        @php $feature = $organization->plan->feature($key); @endphp
        <li>{{ $meta['label'] }}:
          @if ($meta['type'] === 'limit')
            <strong>{{ $feature?->limit_value !== null ? $feature->limit_value : 'ilimitado' }}</strong>
          @else
            <strong>{{ $feature?->enabled ? 'incluído' : 'não incluído' }}</strong>
          @endif
        </li>
      @endforeach
    </ul>
  @endif
</div>

@if ($templates->isNotEmpty())
  <div class="card">
    <h2>Template de campos</h2>
    <p class="muted">Aplica os campos de um template a esta organização (adiciona os que faltam, sem remover os existentes).</p>
    <form method="post" action="{{ route('panel.organizations.template', $organization) }}" class="inline-form">
      @csrf
      <select name="template_id" required>
        @foreach ($templates as $template)<option value="{{ $template->id }}">{{ $template->name }}{{ $template->is_default ? ' (padrão)' : '' }}</option>@endforeach
      </select>
      <button class="btn secondary">Aplicar template</button>
    </form>
  </div>
@endif

<div class="card">
  <h2>Recursos e limites</h2>
  <p class="muted">Precedência: <strong>organização</strong> &gt; plano ({{ $organization->plan?->name ?? '—' }}) &gt; padrão. Deixe "Herdar" para usar o plano.</p>
  <form method="post" action="{{ route('panel.organizations.features', $organization) }}">
    @csrf @method('PUT')
    <table class="table">
      <thead><tr><th>Recurso</th><th>Efetivo</th><th>Consumo</th><th>Override</th></tr></thead>
      <tbody>
        @foreach ($features as $key => $row)
          <tr>
            <td>{{ $row['label'] }}<br><small class="muted">{{ $key }}</small></td>
            <td>
              @if ($row['type'] === 'limit')
                {{ $row['limit'] !== null ? $row['limit'] : 'ilimitado' }}
              @else
                {{ $row['enabled'] ? 'incluído' : 'não incluído' }}
              @endif
            </td>
            <td>{{ $row['type'] === 'limit' ? $row['usage'] : '—' }}</td>
            <td>
              @if ($row['type'] === 'toggle')
                <select name="enabled[{{ $key }}]">
                  <option value="inherit" @selected($row['overrideEnabled'] === null)>Herdar</option>
                  <option value="1" @selected($row['overrideEnabled'] === true)>Ativado</option>
                  <option value="0" @selected($row['overrideEnabled'] === false)>Desativado</option>
                </select>
              @else
                <input type="number" name="limits[{{ $key }}]" min="0" value="{{ $row['overrideLimit'] }}" placeholder="herdar">
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
    <button class="btn secondary">Salvar recursos e limites</button>
  </form>
</div>

@include('organizations.partials.members', ['routeBase' => 'panel.', 'memberships' => $memberships, 'roles' => $roles])
@include('organizations.partials.invites', ['routeBase' => 'panel.', 'invites' => $invites, 'roles' => $roles])
@include('organizations.partials.roles', ['routeBase' => 'panel.', 'roles' => $roles, 'permissions' => $permissions])
@include('organizations.partials.audit', ['audit' => $audit, 'logsRoute' => 'panel.organizations.logs'])
@endsection
