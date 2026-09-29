@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('content')
@if (! auth()->user()->email_verified_at)
  <div class="verify-banner">
    <span>Seu e-mail ainda não foi verificado.</span>
    <form method="post" action="{{ route('verification.send') }}">
      @csrf
      <button class="btn secondary">Reenviar verificação</button>
    </form>
  </div>
@endif
<div class="topbar">
  <div class="container">
    <a href="{{ route('panel.dashboard') }}"><img src="{{ asset('img/logo.svg') }}" alt="QEAI One" width="120"></a>
    <div class="userbox">
      @php $unreadCount = auth()->user()->unreadNotificationsCount(); @endphp
      <a class="bell" href="{{ route('member.notifications.index') }}" aria-label="Notificações">🔔@if ($unreadCount)<span class="badge">{{ $unreadCount }}</span>@endif</a>
      <span>{{ auth()->user()->name }} · {{ auth()->user()->email }}</span>
      <form method="post" action="{{ route('logout') }}">
        @csrf
        <button class="btn secondary">Sair</button>
      </form>
    </div>
  </div>
</div>
<nav class="panel-nav">
  <div class="container">
    <a class="{{ request()->routeIs('panel.dashboard') ? 'active' : '' }}" href="{{ route('panel.dashboard') }}">Visão geral</a>
    <a class="{{ request()->routeIs('panel.organizations.*') ? 'active' : '' }}" href="{{ route('panel.organizations.index') }}">Organizações</a>
    <a class="{{ request()->routeIs('panel.plans.*') ? 'active' : '' }}" href="{{ route('panel.plans.index') }}">Planos</a>
    <a class="{{ request()->routeIs('panel.templates.*') ? 'active' : '' }}" href="{{ route('panel.templates.index') }}">Templates</a>
    <a href="{{ route('member.dashboard') }}">Minhas organizações</a>
    <a class="{{ request()->routeIs('panel.security.*') ? 'active' : '' }}" href="{{ route('panel.security.index') }}">Segurança</a>
    <a class="{{ request()->routeIs('panel.logs.*') ? 'active' : '' }}" href="{{ route('panel.logs.index') }}">Logs</a>
    <a class="{{ request()->routeIs('panel.settings.*') ? 'active' : '' }}" href="{{ route('panel.settings.edit') }}">Configurações</a>
  </div>
</nav>
<main class="container panel-main">
  @if (session('status'))
    <div class="alert ok">{{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert err">{{ $errors->first() }}</div>
  @endif
  @yield('panel')
</main>
@endsection
