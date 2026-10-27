@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('content')
@php
  $user = auth()->user();
  $initials = collect(explode(' ', (string) $user->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: 'QO';
  $currentOrg = $organization ?? null;
  $currentMembership = $currentOrg ? \App\Support\OrgAccess::membership($user, $currentOrg) : null;
  $switcher = $user->is_root_admin
      ? \App\Models\Organization::orderBy('name')->get()
      : \App\Models\Organization::whereHas('memberships', fn ($q) => $q->where('user_id', $user->id)->where('status', 'active'))->orderBy('name')->get();
  $unreadCount = $user->unreadNotificationsCount();
  $canLeads = $currentOrg ? \App\Support\OrgAccess::canData($user, $currentOrg, 'org.leads') : false;
  $canFields = $currentOrg ? \App\Support\OrgAccess::canData($user, $currentOrg, 'org.crm_fields') : false;
  $canSettings = $currentOrg ? \App\Support\OrgAccess::can($user, $currentOrg, 'org.settings') : false;
@endphp

@if (! $user->email_verified_at)
  <div class="verify-banner">
    <span>Seu e-mail ainda não foi verificado.</span>
    <form method="post" action="{{ route('verification.send') }}">
      @csrf
      <button class="btn secondary small">Reenviar verificação</button>
    </form>
  </div>
@endif

<div class="app-shell">
  <aside class="sidebar" id="app-sidebar">
    <div class="sidebar-brand">
      <a href="{{ $currentOrg ? route('member.org.dashboard', $currentOrg) : route('member.dashboard') }}" aria-label="Q One">
        <img src="{{ asset('img/logo-light.svg') }}" alt="Q One" width="104" height="56">
      </a>
      <button class="sidebar-close" type="button" data-nav-close aria-label="Fechar menu">
        <x-icon name="x" :size="20"/>
      </button>
    </div>

    @if ($currentOrg)
      <div class="sidebar-org">
        <span class="avatar square">{{ mb_strtoupper(mb_substr($currentOrg->name, 0, 2)) }}</span>
        <div style="min-width:0;flex:1">
          <strong title="{{ $currentOrg->name }}">{{ \Illuminate\Support\Str::limit($currentOrg->name, 24) }}</strong>
          <small>{{ $currentMembership?->role?->name ?? 'Organização' }} · {{ $currentOrg->plan?->name ?? 'Sem plano' }}</small>
        </div>
      </div>
      @if ($switcher->count() > 1)
        <form method="get" action="{{ route('member.dashboard') }}" onchange="if (this.org.value) window.location.href = this.org.value">
          <select class="org-switch" name="org" aria-label="Trocar de organização" style="width:100%;margin-top:6px;background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.12);color:#CBD5E1;font-size:.73rem;padding:7px 9px">
            <option value="">Trocar de organização…</option>
            @foreach ($switcher as $org)
              <option value="{{ route('member.org.dashboard', $org) }}" @selected($currentOrg->id === $org->id)>{{ $org->name }}</option>
            @endforeach
          </select>
        </form>
      @endif
    @endif

    <nav class="side-nav" aria-label="Navegação principal">
      @if ($currentOrg)
        <span class="nav-label">Principal</span>
        @if ($canLeads && \App\Support\Modules::enabled('dashboard'))
          <a class="side-link {{ request()->routeIs('member.org.dashboard') ? 'active' : '' }}" href="{{ route('member.org.dashboard', $currentOrg) }}">
            <x-icon name="home" :size="18"/> Dashboard
          </a>
        @endif
        @if ($canLeads && \App\Support\Modules::enabled('leads'))
          <a class="side-link {{ request()->routeIs('member.org.contacts.*') ? 'active' : '' }}" href="{{ route('member.org.contacts.index', $currentOrg) }}">
            <x-icon name="users" :size="18"/> Leads
          </a>
        @endif
        @if ($canFields && \App\Support\Modules::enabled('crm_fields'))
          <a class="side-link {{ request()->routeIs('member.org.fields.*') ? 'active' : '' }}" href="{{ route('member.org.fields.index', $currentOrg) }}">
            <x-icon name="list-check" :size="18"/> Gestão de CRM
          </a>
        @endif

        @if ($canSettings || \App\Support\OrgAccess::can($user, $currentOrg, 'org.members') || \App\Support\OrgAccess::can($user, $currentOrg, 'org.logs'))
          <span class="nav-label">Administração</span>
          @if (($canSettings || \App\Support\OrgAccess::can($user, $currentOrg, 'org.members')) && \App\Support\Modules::enabled('settings'))
            <a class="side-link {{ request()->routeIs('member.org.show') ? 'active' : '' }}" href="{{ route('member.org.show', $currentOrg) }}">
              <x-icon name="settings" :size="18"/> Configurações
            </a>
          @endif
          @if (\App\Support\OrgAccess::can($user, $currentOrg, 'org.logs') && \App\Support\Modules::enabled('logs'))
            <a class="side-link {{ request()->routeIs('member.org.logs') ? 'active' : '' }}" href="{{ route('member.org.logs', $currentOrg) }}">
              <x-icon name="shield" :size="18"/> Logs
            </a>
          @endif
        @endif
      @else
        <span class="nav-label">Conta</span>
        <a class="side-link {{ request()->routeIs('member.dashboard') ? 'active' : '' }}" href="{{ route('member.dashboard') }}">
          <x-icon name="building" :size="18"/> Minhas organizações
        </a>
        <a class="side-link {{ request()->routeIs('member.notifications.*') ? 'active' : '' }}" href="{{ route('member.notifications.index') }}">
          <x-icon name="bell" :size="18"/> Notificações
          @if ($unreadCount)<span class="nav-count">{{ $unreadCount }}</span>@endif
        </a>
        @if ($user->is_root_admin)
          <span class="nav-label">Plataforma</span>
          <a class="side-link" href="{{ route('panel.dashboard') }}">
            <x-icon name="shield" :size="18"/> Painel Root
          </a>
        @endif
      @endif
    </nav>

    <div class="sidebar-foot">
      <a class="side-user" href="{{ $currentOrg ? route('member.org.dashboard', $currentOrg) : route('member.dashboard') }}">
        <span class="avatar sm">{{ $initials }}</span>
        <div>
          <strong>{{ $user->name }}</strong>
          <small>{{ $user->is_root_admin ? 'ADM Root' : ($currentMembership?->role?->name ?? 'Membro') }}</small>
        </div>
      </a>
      <form method="post" action="{{ route('logout') }}">
        @csrf
        <button class="side-logout"><x-icon name="logout" :size="17"/> Sair da conta</button>
      </form>
    </div>
  </aside>

  <div class="sidebar-overlay" data-nav-overlay></div>

  <div class="app-main">
    <header class="app-topbar">
      <button class="nav-toggle" type="button" data-nav-toggle aria-label="Abrir menu">
        <x-icon name="menu" :size="20"/>
      </button>

      @if ($currentOrg && $canLeads)
        <form class="searchbar" method="get" action="{{ route('member.org.contacts.index', $currentOrg) }}" role="search">
          <x-icon name="search" :size="18"/>
          <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar leads por nome, e-mail, telefone ou ID..." aria-label="Buscar leads">
        </form>
      @endif

      <div class="topbar-actions">
        <a class="icon-btn" href="{{ route('member.notifications.index') }}" aria-label="Notificações" title="Notificações">
          <x-icon name="bell" :size="19"/>
          @if ($unreadCount)<span class="badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif
        </a>

        <div class="topbar-divider"></div>

        <details class="user-dropdown">
          <summary>
            <span class="avatar">{{ $initials }}</span>
            <span class="user-meta">
              <strong>{{ \Illuminate\Support\Str::limit($user->name, 22) }}</strong>
              <small>{{ $currentOrg ? \Illuminate\Support\Str::limit($currentOrg->name, 24) : 'Minha conta' }}</small>
            </span>
            <x-icon name="chevron-down" :size="16" class="chevron"/>
          </summary>
          <div class="dropdown-menu">
            <div class="dropdown-head">
              <strong>{{ $user->name }}</strong>
              <small>{{ $user->email }}</small>
            </div>
            @if ($currentOrg)
              <a href="{{ route('member.org.dashboard', $currentOrg) }}"><x-icon name="home" :size="16"/> Visão geral</a>
              <a href="{{ route('member.org.show', $currentOrg) }}"><x-icon name="settings" :size="16"/> Configurações</a>
            @endif
            <a href="{{ route('member.dashboard') }}"><x-icon name="building" :size="16"/> Minhas organizações</a>
            @if ($user->is_root_admin)
              <a href="{{ route('panel.dashboard') }}"><x-icon name="shield" :size="16"/> Painel Root</a>
            @endif
            <form method="post" action="{{ route('logout') }}">
              @csrf
              <button class="danger"><x-icon name="logout" :size="16"/> Sair</button>
            </form>
          </div>
        </details>
      </div>
    </header>

    <main class="app-content">
      @if (session('status'))
        <div class="alert ok">{{ session('status') }}</div>
      @endif
      @if ($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
      @endif
      @yield('panel')
    </main>
  </div>
</div>
@endsection
