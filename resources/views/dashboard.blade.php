@extends('layouts.panel')

@section('title', 'Visão geral — QEAI One')

@section('panel')
<div class="page-header">
  <div>
    <span class="page-kicker">Plataforma</span>
    <h1>Olá, {{ auth()->user()->name }}!</h1>
    <p class="muted">Aqui está a visão geral do QEAI One.</p>
  </div>
  <div class="page-actions">
    <a class="btn secondary" href="{{ route('panel.organizations.index') }}"><x-icon name="building" :size="16"/> Organizações</a>
    <a class="btn" href="{{ route('panel.plans.index') }}"><x-icon name="dollar" :size="16"/> Planos</a>
  </div>
</div>

@if (($reportedInvites ?? 0) > 0)
  <div class="alert err">
    <strong>{{ $reportedInvites }}</strong> convite(s) reportado(s) como não reconhecido(s).
    <a href="{{ route('panel.logs.index', ['action' => 'invite.reported']) }}">Revisar agora</a>.
  </div>
@endif

<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-top"><span class="kpi-icon purple"><x-icon name="building" :size="21"/></span></div>
    <div class="kpi-label">Organizações</div>
    <div class="kpi-value">{{ number_format($organizationsCount ?? 0, 0, ',', '.') }}</div>
    <div class="kpi-foot">{{ number_format($activeOrganizations ?? 0, 0, ',', '.') }} ativa(s)</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-top"><span class="kpi-icon blue"><x-icon name="users" :size="21"/></span></div>
    <div class="kpi-label">Usuários</div>
    <div class="kpi-value">{{ number_format($usersCount ?? 0, 0, ',', '.') }}</div>
    <div class="kpi-foot">contas na plataforma</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-top"><span class="kpi-icon orange"><x-icon name="shield" :size="21"/></span></div>
    <div class="kpi-label">Convites reportados</div>
    <div class="kpi-value">{{ number_format($reportedInvites ?? 0, 0, ',', '.') }}</div>
    <div class="kpi-foot">para revisão de segurança</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-top"><span class="kpi-icon green"><x-icon name="activity" :size="21"/></span></div>
    <div class="kpi-label">Status</div>
    <div class="kpi-value" style="font-size:1.25rem;padding-top:7px"><span class="badge ok">Operacional</span></div>
    <div class="kpi-foot">plataforma em execução</div>
  </div>
</div>

<div class="dashboard-grid">
  <section class="panel-card">
    <div class="panel-head">
      <div class="panel-title"><x-icon name="building" :size="18"/><h2>Organizações recentes</h2></div>
      <a class="panel-link" href="{{ route('panel.organizations.index') }}">Ver todas <x-icon name="arrow-right" :size="14"/></a>
    </div>
    @if (($latestOrganizations ?? collect())->isEmpty())
      <div class="empty-state">Nenhuma organização cadastrada ainda.</div>
    @else
      <table class="table">
        <thead><tr><th>Organização</th><th>Membros</th><th>Status</th><th></th></tr></thead>
        <tbody>
          @foreach ($latestOrganizations as $organization)
            <tr>
              <td><strong>{{ $organization->name }}</strong><br><small class="muted">{{ $organization->slug }}</small></td>
              <td>{{ $organization->memberships_count }}</td>
              <td><span class="badge {{ $organization->status === 'active' ? 'ok' : '' }}">{{ $organization->status === 'active' ? 'Ativa' : $organization->status }}</span></td>
              <td class="right"><a class="btn secondary small" href="{{ route('panel.organizations.show', $organization) }}">Detalhes</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </section>

  <section class="panel-card">
    <div class="panel-head">
      <div class="panel-title"><x-icon name="shield" :size="18"/><h2>Segurança e governança</h2></div>
    </div>
    <ul class="onboarding-list">
      <li class="done"><span>✓</span> Autenticação por e-mail com verificação</li>
      <li class="done"><span>✓</span> 2FA disponível para administradores</li>
      <li class="done"><span>✓</span> Permissões por grupo em cada organização</li>
      <li class="done"><span>✓</span> Auditoria com encadeamento de hashes</li>
      <li class="done"><span>✓</span> Convites com consentimento e canal de denúncia</li>
    </ul>
    <div class="btn-row" style="margin-top:16px">
      <a class="btn secondary small" href="{{ route('panel.logs.index') }}"><x-icon name="shield" :size="15"/> Ver logs</a>
      <a class="btn secondary small" href="{{ route('panel.security.index') }}"><x-icon name="key" :size="15"/> Segurança</a>
    </div>
  </section>
</div>
@endsection
