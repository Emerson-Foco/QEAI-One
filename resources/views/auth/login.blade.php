@extends('layouts.app')

@section('title', 'Entrar — QEAI One')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="center">
  <div class="card narrow">
    <div class="brand"><img src="{{ asset('img/logo.svg') }}" alt="QEAI One"></div>
    <span class="eyebrow">Acesso</span>
    <h1>Bem-vindo de volta.</h1>
    <p class="muted">Entre com seu e-mail para acessar o painel.</p>

    @if (session('status'))
      <div class="alert ok">{{ session('status') }}</div>
    @endif
    @error('email')<div class="alert err">{{ $message }}</div>@enderror

    <form method="post" action="{{ route('login') }}">
      @csrf
      <label>E-mail<input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></label>
      <label>Senha<input type="password" name="password" required autocomplete="current-password"></label>
      <label class="check"><input type="checkbox" name="remember" value="1"> Lembrar de mim</label>
      <button class="btn full">Entrar</button>
    </form>
    <p class="muted" style="margin-top:14px;font-size:.8rem"><a href="{{ route('password.request') }}">Esqueci minha senha</a></p>
  </div>
</div>
@endsection
