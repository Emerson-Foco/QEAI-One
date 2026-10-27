@extends('layouts.member')

@section('title', 'Minhas organizações — Q One')

@section('panel')
@php $firstName = collect(explode(' ', (string) auth()->user()->name))->filter()->first() ?: 'você'; @endphp

<div class="page-header">
  <div>
    <span class="page-kicker">Minha conta</span>
    <h1>Olá, {{ $firstName }}!</h1>
    <p class="muted">Escolha uma organização para acessar o CRM e a operação.</p>
  </div>
  @if (auth()->user()->is_root_admin)
    <div class="page-actions">
      <a class="btn secondary" href="{{ route('panel.dashboard') }}"><x-icon name="shield" :size="16"/> Painel Root</a>
    </div>
  @endif
</div>

@if ($memberships->isEmpty())
  <div class="card">
    <div class="empty-state" style="border:0;background:transparent">
      <x-icon name="building" :size="30"/>
      <h2 style="margin-top:8px">Nenhuma organização por aqui</h2>
      <p class="muted">Você ainda não participa de nenhuma organização. Peça um convite ao administrador ou crie sua própria empresa.</p>
      <a class="btn" href="{{ route('register') }}">Criar minha empresa</a>
    </div>
  </div>
@else
  <div class="org-grid">
    @foreach ($memberships as $membership)
      <article class="org-card">
        <div class="org-card-head">
          <span class="avatar square">{{ mb_strtoupper(mb_substr($membership->organization->name, 0, 2)) }}</span>
          <div style="min-width:0">
            <strong>{{ $membership->organization->name }}</strong>
            <small>{{ $membership->role?->name ?? 'Membro' }} · Plano {{ $membership->organization->plan?->name ?? '—' }}</small>
          </div>
        </div>
        <div class="org-card-meta">
          <span class="badge"><x-icon name="users" :size="12"/> {{ $membership->organization->memberships()->where('status', 'active')->count() }} membro(s)</span>
          <span class="badge ok"><x-icon name="activity" :size="12"/> Ativa</span>
        </div>
        <a class="btn" href="{{ route('member.org.dashboard', $membership->organization) }}">
          Abrir organização <x-icon name="arrow-right" :size="16"/>
        </a>
      </article>
    @endforeach
  </div>

  <div class="card" style="margin-top:22px">
    <div class="card-head">
      <div>
        <h2>Precisa de outra organização?</h2>
        <p class="muted">Cada empresa tem sua própria base de leads, equipe e permissões.</p>
      </div>
    </div>
    <a class="btn secondary" href="{{ route('register') }}"><x-icon name="plus" :size="16"/> Criar nova empresa</a>
  </div>
@endif
@endsection
