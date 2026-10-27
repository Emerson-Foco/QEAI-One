@extends('layouts.auth')

@section('title', 'Criar conta — Q One')

@section('auth')
  <h1 class="auth-title">Crie sua organização</h1>
  <p class="auth-sub">Comece grátis. Você será o proprietário da conta.</p>

  @if ($errors->any())
    <div class="alert err">{{ $errors->first() }}</div>
  @endif

  <form method="post" action="{{ route('register') }}" class="auth-form">
    @csrf
    <label for="company_name">Empresa / organização
      <input id="company_name" name="company_name" value="{{ old('company_name') }}" placeholder="Nome da sua empresa" required maxlength="160" autocomplete="organization">
    </label>

    <label for="document"><span>CNPJ <small>(opcional)</small></span>
      <input id="document" name="document" value="{{ old('document') }}" placeholder="00.000.000/0000-00" maxlength="24" inputmode="numeric">
    </label>

    <label for="name">Seu nome
      <input id="name" name="name" value="{{ old('name') }}" placeholder="Nome e sobrenome" required maxlength="120" autocomplete="name">
    </label>

    <label for="email">E-mail
      <span class="auth-input">
        <x-icon name="mail" :size="18"/>
        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="voce@empresa.com" required maxlength="180" autocomplete="username">
      </span>
    </label>

    <div class="auth-grid">
      <label for="password">Senha
        <span class="auth-input">
          <x-icon name="lock" :size="18"/>
          <input id="password" type="password" name="password" minlength="10" maxlength="72" placeholder="Mín. 10" required autocomplete="new-password" data-password>
        </span>
      </label>
      <label for="password_confirmation">Confirmar senha
        <span class="auth-input">
          <x-icon name="lock" :size="18"/>
          <input id="password_confirmation" type="password" name="password_confirmation" minlength="10" maxlength="72" placeholder="Repetir" required autocomplete="new-password">
        </span>
      </label>
    </div>

    <label class="check auth-terms">
      <input type="checkbox" name="terms" value="1" required @checked(old('terms'))>
      <span>Li e aceito os <a href="{{ config('legal.terms_url') }}" target="_blank" rel="noopener">Termos de Uso</a> e a <a href="{{ config('legal.privacy_url') }}" target="_blank" rel="noopener">Política de Privacidade</a>.</span>
    </label>

    <button class="btn auth-submit">
      Criar minha conta
      <x-icon name="arrow-right" :size="18"/>
    </button>
  </form>

  <p class="auth-bottom">Já tem uma conta? <a href="{{ route('login') }}">Entrar</a></p>
@endsection
