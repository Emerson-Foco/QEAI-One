@extends('layouts.auth')

@section('title', 'Definir nova senha — Q One')

@section('auth')
  <h1 class="auth-title">Crie sua nova senha</h1>
  <p class="auth-sub">Para <strong>{{ $email }}</strong>.</p>

  @if ($errors->any())
    <div class="alert err">{{ $errors->first() }}</div>
  @endif

  <form method="post" action="{{ route('password.update') }}" class="auth-form">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="email" value="{{ $email }}">
    <label for="password">Nova senha
      <span class="auth-input">
        <x-icon name="lock" :size="18"/>
        <input id="password" type="password" name="password" minlength="10" maxlength="72" placeholder="Mínimo de 10 caracteres" required autocomplete="new-password" data-password>
        <button type="button" class="auth-toggle" data-toggle-password aria-label="Mostrar senha"><x-icon name="eye" :size="18"/></button>
      </span>
    </label>
    <label for="password_confirmation">Confirme a nova senha
      <span class="auth-input">
        <x-icon name="lock" :size="18"/>
        <input id="password_confirmation" type="password" name="password_confirmation" minlength="10" maxlength="72" placeholder="Repita a senha" required autocomplete="new-password">
      </span>
    </label>
    <button class="btn auth-submit">Salvar nova senha <x-icon name="arrow-right" :size="18"/></button>
  </form>

  <p class="auth-bottom"><a href="{{ route('login') }}">Voltar ao login</a></p>
@endsection
