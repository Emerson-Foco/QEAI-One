@extends('layouts.member')

@section('title', $organization->name . ' — QEAI One')

@section('panel')
<div class="page-head">
  <div>
    <span class="pill">Organização</span>
    <h1>{{ $organization->name }}</h1>
    <p class="muted">
      Plano: <strong>{{ $organization->plan?->name ?? '—' }}</strong>
      @if ($membership) · Seu grupo: <strong>{{ $membership->role?->name ?? '—' }}</strong> @endif
    </p>
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
