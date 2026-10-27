@extends('layouts.app')

@section('title', 'Convite — Q One')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="center">
  <div class="card narrow">
    <div class="brand"><img src="{{ asset('img/logo.svg') }}" alt="Q One"></div>
    <span class="eyebrow">Convite</span>
    <h1>Participe de {{ $invite->organization->name }}</h1>
    <p class="muted">Convite para <strong>{{ $invite->email }}</strong>, no grupo <strong>{{ $invite->role?->name ?? 'Membro' }}</strong>. Defina seus dados para entrar.</p>

    @if ($errors->any())
      <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="post" action="{{ url('/convite/' . $token) }}">
      @csrf
      <label>Seu nome<input name="name" value="{{ old('name') }}" required maxlength="120"></label>
      <label>Senha<input type="password" name="password" minlength="10" maxlength="72" required autocomplete="new-password"><small>Mínimo de 10 caracteres.</small></label>
      <label>Confirme a senha<input type="password" name="password_confirmation" minlength="10" maxlength="72" required></label>
      <label class="check">
        <input type="checkbox" name="terms" value="1" required>
        <span>Li e aceito os <a href="{{ config('legal.terms_url') }}" target="_blank" rel="noopener">Termos de Uso</a> e a <a href="{{ config('legal.privacy_url') }}" target="_blank" rel="noopener">Política de Privacidade</a>.</span>
      </label>
      <button class="btn full">Aceitar convite</button>
    </form>

    <p class="muted" style="margin-top:18px;font-size:.78rem">
      Não reconhece este convite?
      <a href="{{ route('invite.report.form', $token) }}">Informe aqui</a>.
    </p>
  </div>
</div>
@endsection
