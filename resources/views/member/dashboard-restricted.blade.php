@extends('layouts.member')

@section('title', 'Acesso restrito — ' . $organization->name)

@section('panel')
<div class="page-header">
  <div>
    <span class="page-kicker">Acesso</span>
    <h1>{{ $organization->name }}</h1>
    <p class="muted">Esta organização possui regras de acesso definidas pelo administrador.</p>
  </div>
  <div class="page-actions">
    <a class="btn secondary" href="{{ route('member.org.show', $organization) }}"><x-icon name="settings" :size="16"/> Configurações da organização</a>
  </div>
</div>

<div class="card">
  <div class="empty-state" style="border:0;background:transparent;padding:26px 10px">
    <x-icon name="shield" :size="34"/>
    <h2 style="margin-top:10px">Acesso aos dados</h2>
    @if ($isRootWithoutMembership)
      <p class="muted" style="max-width:560px;margin:0 auto 14px">
        Você é <strong>ADM Root</strong>, mas não possui vínculo com esta organização. Para acessar
        leads, oportunidades e atendimento, é necessário que um administrador da organização
        conceda a você um grupo de acesso com a permissão <strong>Leads e CRM</strong>.
      </p>
    @else
      <p class="muted" style="max-width:560px;margin:0 auto 14px">
        Seu grupo de acesso não inclui a permissão <strong>Leads e CRM</strong>. Peça a um administrador
        da organização para ajustar suas permissões.
      </p>
    @endif
    <div class="btn-row" style="justify-content:center">
      <a class="btn secondary" href="{{ route('member.dashboard') }}"><x-icon name="building" :size="16"/> Minhas organizações</a>
      <a class="btn" href="{{ route('member.org.show', $organization) }}"><x-icon name="settings" :size="16"/> Ver configurações</a>
    </div>
  </div>
</div>

@if (auth()->user()->is_root_admin)
  <div class="card">
    <div class="card-head">
      <div>
        <h2>Painel da plataforma</h2>
        <p class="muted">Ações administrativas continuam disponíveis no painel Root e ficam registradas nos logs.</p>
      </div>
    </div>
    <div class="btn-row">
      <a class="btn secondary" href="{{ route('panel.organizations.show', $organization) }}"><x-icon name="building" :size="16"/> Detalhes na plataforma</a>
      <a class="btn secondary" href="{{ route('panel.logs.index') }}"><x-icon name="shield" :size="16"/> Logs da plataforma</a>
    </div>
  </div>
@endif
@endsection
