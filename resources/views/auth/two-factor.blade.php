@extends('layouts.app')

@section('title', 'Verificação em duas etapas — QEAI One')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="center">
  <div class="card narrow">
    <div class="brand"><img src="{{ asset('img/logo.svg') }}" alt="QEAI One"></div>
    <span class="eyebrow">Verificação</span>
    <h1>Confirme o código.</h1>
    <p class="muted">Abra seu app autenticador e informe o código de 6 dígitos.</p>

    @if ($errors->any())
      <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="post" action="{{ route('two-factor.verify') }}">
      @csrf
      <label>Código<input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus></label>
      <button class="btn full">Confirmar</button>
    </form>
    <p class="muted" style="margin-top:14px;font-size:.8rem"><a href="{{ route('login') }}">Voltar ao login</a></p>
  </div>
</div>
@endsection
