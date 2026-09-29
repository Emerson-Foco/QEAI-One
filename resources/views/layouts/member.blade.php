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
    <a href="{{ route('member.dashboard') }}"><img src="{{ asset('img/logo.svg') }}" alt="QEAI One" width="120"></a>
    <div class="userbox">
      @php
        $switcher = auth()->user()->is_root_admin
          ? \App\Models\Organization::orderBy('name')->get()
          : \App\Models\Organization::whereHas('memberships', fn ($q) => $q->where('user_id', auth()->id())->where('status', 'active'))->orderBy('name')->get();
      @endphp
      @if ($switcher->count() > 1)
        <select aria-label="Trocar de organização" onchange="if (this.value) window.location.href = this.value">
          <option value="">Trocar de organização…</option>
          @foreach ($switcher as $org)
            <option value="{{ route('member.org.show', $org) }}" @selected(isset($organization) && $organization && $organization->id === $org->id)>{{ $org->name }}</option>
          @endforeach
        </select>
      @endif
      <span>{{ auth()->user()->name }}</span>
      <form method="post" action="{{ route('logout') }}">
        @csrf
        <button class="btn secondary">Sair</button>
      </form>
    </div>
  </div>
</div>
@isset($organization)
  @if ($organization)
    <nav class="panel-nav">
      <div class="container">
        <a class="{{ request()->routeIs('member.org.show') ? 'active' : '' }}" href="{{ route('member.org.show', $organization) }}">Visão geral</a>
        @if (\App\Support\OrgAccess::canData(auth()->user(), $organization, 'org.leads'))
          <a class="{{ request()->routeIs('member.org.contacts.*') ? 'active' : '' }}" href="{{ route('member.org.contacts.index', $organization) }}">Contatos</a>
          <a class="{{ request()->routeIs('member.org.companies.*') ? 'active' : '' }}" href="{{ route('member.org.companies.index', $organization) }}">Empresas</a>
          <a class="{{ request()->routeIs('member.org.pipeline.*') || request()->routeIs('member.org.deals.*') ? 'active' : '' }}" href="{{ route('member.org.pipeline.index', $organization) }}">Negócios</a>
          <a class="{{ request()->routeIs('member.org.tasks.*') ? 'active' : '' }}" href="{{ route('member.org.tasks.index', $organization) }}">Tarefas</a>
        @endif
        @if (! empty($canLogs))
          <a class="{{ request()->routeIs('member.org.logs') ? 'active' : '' }}" href="{{ route('member.org.logs', $organization) }}">Logs</a>
        @endif
        <span class="spacer"></span>
        <a href="{{ route('member.dashboard') }}">Minhas organizações</a>
      </div>
    </nav>
  @endif
@endisset
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
