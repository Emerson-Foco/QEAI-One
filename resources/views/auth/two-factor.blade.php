@extends('layouts.auth')

@section('title', 'Verificação em duas etapas — QEAI One')

@section('auth')
  <h1 class="auth-title">Confirme o código</h1>
  <p class="auth-sub">Abra seu app autenticador e informe o código de 6 dígitos.</p>

  @if ($errors->any())
    <div class="alert err">{{ $errors->first() }}</div>
  @endif

  <form method="post" action="{{ route('two-factor.verify') }}" class="auth-form">
    @csrf
    <label for="code">Código
      <span class="auth-input auth-code">
        <x-icon name="key" :size="18"/>
        <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000" required autofocus>
      </span>
    </label>
    <button class="btn auth-submit">Confirmar <x-icon name="arrow-right" :size="18"/></button>
  </form>

  <p class="auth-bottom"><a href="{{ route('login') }}">Voltar ao login</a></p>
@endsection
