@extends('layouts.app')

@section('title', 'Limite atingido — QEAI One')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="center">
  <div class="card narrow">
    <div class="brand"><img src="{{ asset('img/logo.svg') }}" alt="QEAI One"></div>
    <span class="eyebrow">Convite</span>
    <h1>Limite de usuários atingido.</h1>
    <p class="muted">
      A organização <strong>{{ $invite->organization?->name }}</strong> atingiu o limite de usuários do plano atual.
      Peça ao administrador da organização para ajustar o plano ou os limites antes de aceitar este convite.
    </p>
    <a class="btn secondary" href="{{ route('home') }}">Voltar ao início</a>
  </div>
</div>
@endsection
