@extends('layouts.app')

@section('title', 'QEAI One — a plataforma de marketing e atendimento')
@section('body_class', 'landing')

@section('content')
<div class="topbar">
  <div class="container">
    <img src="{{ asset('img/logo.svg') }}" alt="QEAI One" width="120">
    <a class="btn secondary" href="{{ route('login') }}">Entrar</a>
  </div>
</div>
<header class="hero">
  <div class="container">
    <span class="pill">QEAI One</span>
    <h1>Sua operação de marketing e atendimento, em um só lugar.</h1>
    <p class="muted" style="max-width:640px">CRM, captação de leads, atendimento omnicanal, automações e inteligência artificial — em módulos que se encaixam, no ritmo do seu negócio.</p>
    <div class="btn-row">
      <a class="btn" href="{{ route('register') }}">Criar conta</a>
      <a class="btn secondary" href="{{ route('login') }}">Entrar</a>
    </div>
  </div>
</header>
@endsection
