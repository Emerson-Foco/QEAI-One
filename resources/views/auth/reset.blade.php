@extends('layouts.app')

@section('title', 'Definir nova senha — QEAI One')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="center">
  <div class="card narrow">
    <div class="brand"><img src="{{ asset('img/logo.svg') }}" alt="QEAI One"></div>
    <span class="eyebrow">Nova senha</span>
    <h1>Crie sua nova senha.</h1>
    <p class="muted">Para <strong>{{ $email }}</strong>.</p>

    @if ($errors->any())
      <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="post" action="{{ route('password.update') }}">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <input type="hidden" name="email" value="{{ $email }}">
      <label>Nova senha<input type="password" name="password" minlength="10" maxlength="72" required autocomplete="new-password"><small>Mínimo de 10 caracteres.</small></label>
      <label>Confirme a nova senha<input type="password" name="password_confirmation" minlength="10" maxlength="72" required></label>
      <button class="btn full">Salvar nova senha</button>
    </form>
  </div>
</div>
@endsection
