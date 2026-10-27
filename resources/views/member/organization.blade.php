@extends('layouts.member')

@section('title', $organization->name . ' — Q One')

@section('panel')
<div class="page-head">
  <div>
    <span class="pill">Configurações</span>
    <h1>{{ $organization->name }}</h1>
    <p class="muted">
      Plano: <strong>{{ $organization->plan?->name ?? '—' }}</strong>
      @if ($membership) · Seu grupo: <strong>{{ $membership->role?->name ?? '—' }}</strong> @endif
    </p>
  </div>
  <div class="btn-row">
    <a class="btn secondary" href="{{ route('member.org.dashboard', $organization) }}"><x-icon name="home" :size="16"/> Ir para o dashboard</a>
  </div>
</div>

<div class="card">
  <h2>Plano e limites</h2>
  <p class="muted">Plano atual: <strong>{{ $organization->plan?->name ?? '—' }}</strong></p>
  <ul class="feature-list">
    @foreach ($features as $key => $row)
      <li>{{ $row['label'] }}:
        <strong>
          @if ($row['type'] === 'limit')
            {{ $row['limit'] !== null ? $row['limit'] . ' (em uso: ' . $row['usage'] . ')' : 'ilimitado' }}
          @else
            {{ $row['enabled'] ? 'incluído' : 'não incluído' }}
          @endif
        </strong>
      </li>
    @endforeach
  </ul>
</div>

<div class="card">
  <h2>Módulos</h2>
  <p class="muted">Nesta etapa, o Q One está focado no essencial: dashboard, leads e configuração do CRM. Os demais módulos continuam preservados e podem ser reativados quando fizer sentido.</p>
  <div class="module-grid">
    @foreach (\App\Support\Modules::catalog() as $key => $label)
      @php $enabled = \App\Support\Modules::enabled($key); @endphp
      <div class="module-item {{ $enabled ? '' : 'is-off' }}">
        <span class="module-dot"></span>
        <div><strong>{{ $label }}</strong><small>{{ $enabled ? 'Ativo' : 'Desativado nesta etapa' }}</small></div>
        <span class="badge {{ $enabled ? 'ok' : '' }}">{{ $enabled ? 'ativo' : 'off' }}</span>
      </div>
    @endforeach
  </div>
</div>

@if ($onboardingPending && ! empty($onboarding))  <div class="card">
    <h2>Primeiros passos</h2>
    <ul class="onboarding-list">
      @foreach ($onboarding as $step)
        <li class="{{ $step['done'] ? 'done' : '' }}">
          <span>{{ $step['done'] ? '✓' : '○' }}</span>
          @if ($step['done']) {{ $step['label'] }} @else <a href="{{ $step['url'] }}">{{ $step['label'] }}</a> @endif
        </li>
      @endforeach
    </ul>
  </div>
@endif

@if ($canMembers)
  @include('organizations.partials.members', ['routeBase' => 'member.org.', 'memberships' => $memberships, 'roles' => $roles])
  @include('organizations.partials.invites', ['routeBase' => 'member.org.', 'invites' => $invites, 'roles' => $roles])
  @include('organizations.partials.roles', ['routeBase' => 'member.org.', 'roles' => $roles, 'permissions' => $permissions])
@endif

@if ($canLogs)
  @include('organizations.partials.audit', ['audit' => $audit, 'logsRoute' => 'member.org.logs'])
@endif

@unless ($canMembers || $canLogs)
  <div class="card">
    <h2>Bem-vindo(a) à {{ $organization->name }}</h2>
    <p class="muted">Seu grupo de acesso não permite gerenciar esta organização. Fale com um administrador se precisar de acesso.</p>
  </div>
@endunless
@endsection
