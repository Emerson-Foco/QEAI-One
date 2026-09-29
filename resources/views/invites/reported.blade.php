@extends('layouts.app')

@section('title', 'Registrado — QEAI One')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="center">
  <div class="card narrow">
    <div class="brand"><img src="{{ asset('img/logo.svg') }}" alt="QEAI One"></div>
    <span class="eyebrow">Segurança</span>
    <h1>Registramos sua informação.</h1>
    <p class="muted">O convite foi bloqueado e o caso entrou na nossa trilha de auditoria para análise. Obrigado por avisar.</p>
    <a class="btn secondary" href="{{ route('home') }}">Voltar ao início</a>
  </div>
</div>
@endsection
