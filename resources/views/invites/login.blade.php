@extends('layouts.app')

@section('title', 'Aceitar convite — QEAI One')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="center">
  <div class="card narrow">
    <div class="brand"><img src="{{ asset('img/logo.svg') }}" alt="QEAI One"></div>
    <span class="eyebrow">Convite</span>
    <h1>Você já tem conta.</h1>
    <p class="muted">
      O convite é para o e-mail <strong>{{ $invite->email }}</strong>. Entre com essa conta para aceitar o convite
      de <strong>{{ $invite->organization->name }}</strong>.
    </p>

    @if (! empty($logoutMismatch))
      <div class="alert err">Você está conectado com outro e-mail. Saia e entre com {{ $invite->email }}.</div>
    @endif

    <a class="btn full" href="{{ route('login') }}">Entrar para aceitar</a>

    <p class="muted" style="margin-top:18px;font-size:.78rem">
      Não reconhece este convite? <a href="{{ route('invite.report.form', $token) }}">Informe aqui</a>.
    </p>
  </div>
</div>
@endsection
