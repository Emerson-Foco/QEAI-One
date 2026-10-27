@extends('layouts.auth')

@section('title', 'Esqueci minha senha — QEAI One')

@section('auth')
  <h1 class="auth-title">Esqueceu a senha?</h1>
  <p class="auth-sub">Informe seu e-mail e enviaremos um link para redefinir a senha.</p>

  @if (session('status'))
    <div class="alert ok">{{ session('status') }}</div>
  @endif
  @error('email')<div class="alert err">{{ $message }}</div>@enderror

  <form method="post" action="{{ route('password.email') }}" class="auth-form">
    @csrf
    <label for="email">E-mail
      <span class="auth-input">
        <x-icon name="mail" :size="18"/>
        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="seu@empresa.com" required autofocus autocomplete="username">
      </span>
    </label>
    <button class="btn auth-submit">Enviar link <x-icon name="arrow-right" :size="18"/></button>
  </form>

  <p class="auth-bottom"><a href="{{ route('login') }}">Voltar ao login</a></p>
@endsection
