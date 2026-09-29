@extends('layouts.app')

@section('title', 'Criar conta — QEAI One')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="center">
  <div class="card narrow">
    <div class="brand"><img src="{{ asset('img/logo.svg') }}" alt="QEAI One"></div>
    <span class="eyebrow">Criar conta</span>
    <h1>Crie sua organização no QEAI One.</h1>
    <p class="muted">Para empresas (B2B). Você será o proprietário da organização.</p>

    @if ($errors->any())
      <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="post" action="{{ route('register') }}">
      @csrf
      <label>Empresa / organização<input name="company_name" value="{{ old('company_name') }}" required maxlength="160"></label>
      <label>CNPJ (opcional)<input name="document" value="{{ old('document') }}" maxlength="24"></label>
      <div class="grid-2">
        <label>Seu nome<input name="name" value="{{ old('name') }}" required maxlength="120"></label>
        <label>E-mail<input type="email" name="email" value="{{ old('email') }}" required maxlength="180" autocomplete="username"></label>
        <label>Senha<input type="password" name="password" minlength="10" maxlength="72" required autocomplete="new-password"><small>Mínimo de 10 caracteres.</small></label>
        <label>Confirme a senha<input type="password" name="password_confirmation" minlength="10" maxlength="72" required autocomplete="new-password"></label>
      </div>
      <label class="check">
        <input type="checkbox" name="terms" value="1" required>
        <span>Li e aceito os <a href="https://qeai.com.br/privacidade" target="_blank" rel="noopener">Termos de Uso e Política de Privacidade</a>.</span>
      </label>
      <button class="btn full">Criar conta</button>
    </form>

    <p class="muted" style="margin-top:14px;font-size:.8rem">Já tem conta? <a href="{{ route('login') }}">Entrar</a></p>
  </div>
</div>
@endsection
