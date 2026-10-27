@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('content')
@php
  $user = auth()->user();
  $initials = collect(explode(' ', (string) $user->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: 'QO';
  $unreadCount = $user->unreadNotificationsCount();
  $reportedInvites = $reportedInvites ?? 0;
@endphp

<div class="app-shell">
  <aside class="sidebar" id="app-sidebar">
    <div class="sidebar-brand">
      <a href="{{ route('panel.dashboard') }}" aria-label="Q One">
        <img src="{{ asset('img/logo-light.svg') }}" alt="Q One" width="104" height="56">
      </a>
      <button class="sidebar-close" type="button" data-nav-close aria-label="Fechar menu">
        <x-icon name="x" :size="20"/>
      </button>
    </div>

    <div class="sidebar-org">
      <span class="avatar square" style="background:linear-gradient(135deg,#F97316,#FB923C)"><x-icon name="shield" :size="18"/></span>
      <div style="min-width:0;flex:1">
        <strong>Painel Root</strong>
        <small>Administração da plataforma</small>
      </div>
    </div>

    <nav class="side-nav" aria-label="Navegação do painel">
      <span class="nav-label">Plataforma</span>
      <a class="side-link {{ request()->routeIs('panel.dashboard') ? 'active' : '' }}" href="{{ route('panel.dashboard') }}">
        <x-icon name="home" :size="18"/> Visão geral
      </a>
      <a class="side-link {{ request()->routeIs('panel.organizations.*') ? 'active' : '' }}" href="{{ route('panel.organizations.index') }}">
        <x-icon name="building" :size="18"/> Organizações
      </a>
      <a class="side-link {{ request()->routeIs('panel.plans.*') ? 'active' : '' }}" href="{{ route('panel.plans.index') }}">
        <x-icon name="dollar" :size="18"/> Planos
      </a>
      <a class="side-link {{ request()->routeIs('panel.templates.*') ? 'active' : '' }}" href="{{ route('panel.templates.index') }}">
        <x-icon name="list-check" :size="18"/> Templates
      </a>

      <span class="nav-label">Operação</span>
      <a class="side-link" href="{{ route('member.dashboard') }}">
        <x-icon name="grid" :size="18"/> Minhas organizações
      </a>
      <a class="side-link {{ request()->routeIs('member.notifications.*') ? 'active' : '' }}" href="{{ route('member.notifications.index') }}">
        <x-icon name="bell" :size="18"/> Notificações
        @if ($unreadCount)<span class="nav-count">{{ $unreadCount }}</span>@endif
      </a>

      <span class="nav-label">Sistema</span>
      <a class="side-link {{ request()->routeIs('panel.security.*') ? 'active' : '' }}" href="{{ route('panel.security.index') }}">
        <x-icon name="key" :size="18"/> Segurança
      </a>
      <a class="side-link {{ request()->routeIs('panel.logs.*') ? 'active' : '' }}" href="{{ route('panel.logs.index') }}">
        <x-icon name="shield" :size="18"/> Logs
        @if ($reportedInvites > 0)<span class="nav-count" style="background:#F97316;color:#fff">{{ $reportedInvites }}</span>@endif
      </a>
      <a class="side-link {{ request()->routeIs('panel.settings.*') ? 'active' : '' }}" href="{{ route('panel.settings.edit') }}">
        <x-icon name="settings" :size="18"/> Configurações
      </a>
    </nav>

    <div class="sidebar-foot">
      <a class="side-user" href="{{ route('panel.security.index') }}">
        <span class="avatar sm" style="background:linear-gradient(135deg,#F97316,#FB923C)">{{ $initials }}</span>
        <div>
          <strong>{{ $user->name }}</strong>
          <small>ADM Root</small>
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
      <div style="display:flex;align-items:center;gap:9px;min-width:0">
        <span class="root-badge on-light"><x-icon name="shield" :size="12"/> Root Admin</span>
        <span class="muted nowrap" style="font-size:.78rem">Painel da plataforma</span>
      </div>

      <div class="topbar-actions">
        <a class="btn secondary small" href="{{ route('home') }}" target="_blank" rel="noopener">
          <x-icon name="link" :size="15"/> Ver site
        </a>
        <a class="icon-btn" href="{{ route('member.notifications.index') }}" aria-label="Notificações" title="Notificações">
          <x-icon name="bell" :size="19"/>
          @if ($unreadCount)<span class="badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif
        </a>
        <div class="topbar-divider"></div>
        <details class="user-dropdown">
          <summary>
            <span class="avatar" style="background:linear-gradient(135deg,#F97316,#FB923C)">{{ $initials }}</span>
            <span class="user-meta">
              <strong>{{ \Illuminate\Support\Str::limit($user->name, 22) }}</strong>
              <small>ADM Root</small>
            </span>
            <x-icon name="chevron-down" :size="16" class="chevron"/>
          </summary>
          <div class="dropdown-menu">
            <div class="dropdown-head">
              <strong>{{ $user->name }}</strong>
              <small>{{ $user->email }}</small>
            </div>
            <a href="{{ route('panel.settings.edit') }}"><x-icon name="settings" :size="16"/> Configurações</a>
            <a href="{{ route('panel.security.index') }}"><x-icon name="key" :size="16"/> Segurança</a>
            <a href="{{ route('member.dashboard') }}"><x-icon name="grid" :size="16"/> Minhas organizações</a>
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
