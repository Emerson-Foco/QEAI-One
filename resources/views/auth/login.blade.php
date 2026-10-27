@extends('layouts.auth')

@section('title', 'Entrar — QEAI One')

@section('auth')
  <h1 class="auth-title">Acesse sua conta</h1>
  <p class="auth-sub">Entre para continuar no QEAI One</p>

  @if (session('status'))
    <div class="alert ok">{{ session('status') }}</div>
  @endif
  @error('email')<div class="alert err">{{ $message }}</div>@enderror

  <form method="post" action="{{ route('login') }}" class="auth-form">
    @csrf
    <label for="email">E-mail
      <span class="auth-input">
        <x-icon name="mail" :size="18"/>
        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="seu@empresa.com" required autofocus autocomplete="username">
      </span>
    </label>

    <label for="password">Senha
      <span class="auth-input">
        <x-icon name="lock" :size="18"/>
        <input id="password" type="password" name="password" placeholder="Sua senha" required autocomplete="current-password" data-password>
        <button type="button" class="auth-toggle" data-toggle-password aria-label="Mostrar senha">
          <x-icon name="eye" :size="18"/>
        </button>
      </span>
    </label>

    <div class="auth-row">
      <label class="check" style="margin:0">
        <input type="checkbox" name="remember" value="1">
        <span>Lembrar de mim</span>
      </label>
      <a class="auth-link" href="{{ route('password.request') }}">Esqueceu a senha?</a>
    </div>

    <button class="btn auth-submit">
      Entrar
      <x-icon name="arrow-right" :size="18"/>
    </button>
  </form>

  <div class="auth-divider"><span>ou</span></div>

  <a class="btn secondary auth-alt" href="{{ route('register') }}">
    <x-icon name="building" :size="18"/>
    Criar conta para minha empresa
  </a>

  <p class="auth-bottom">Ainda não tem uma conta? <a href="{{ route('register') }}">Cadastre sua empresa</a></p>
@endsection
