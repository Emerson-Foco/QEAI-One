@extends('layouts.app')

@section('title', 'Esqueci minha senha — QEAI One')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="center">
  <div class="card narrow">
    <div class="brand"><img src="{{ asset('img/logo.svg') }}" alt="QEAI One"></div>
    <span class="eyebrow">Recuperar acesso</span>
    <h1>Esqueceu a senha?</h1>
    <p class="muted">Informe seu e-mail e enviaremos um link para redefinir a senha.</p>

    @if (session('status'))
      <div class="alert ok">{{ session('status') }}</div>
    @endif
    @error('email')<div class="alert err">{{ $message }}</div>@enderror

    <form method="post" action="{{ route('password.email') }}">
      @csrf
      <label>E-mail<input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
      <button class="btn full">Enviar link</button>
    </form>
    <p class="muted" style="margin-top:14px;font-size:.8rem"><a href="{{ route('login') }}">Voltar ao login</a></p>
  </div>
</div>
@endsection
