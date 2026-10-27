@extends('layouts.auth')

@section('title', 'Termos de Uso — Q One')

@section('auth')
  <h1 class="auth-title">Antes de continuar</h1>
  <p class="auth-sub">Precisamos do seu aceite para usar a plataforma.</p>

  <div class="legal-summary">
    <p>O Q One é uma plataforma para organizar leads, campos e informações comerciais.</p>
    <p>A organização e seus usuários são responsáveis pelos dados que inserem, coletam ou armazenam, incluindo dados de leads e clientes.</p>
    <p>O Q One atua como fornecedor de infraestrutura e armazenamento das informações inseridas pelo usuário.</p>
  </div>

  <p class="muted" style="font-size:.78rem">
    Leia os <a href="{{ config('legal.terms_url') }}" target="_blank" rel="noopener">Termos de Uso</a>
    e a <a href="{{ config('legal.privacy_url') }}" target="_blank" rel="noopener">Política de Privacidade</a>.
  </p>

  <form method="post" action="{{ route('legal.accept.store') }}">
    @csrf
    <button class="btn auth-submit">Li e aceito os Termos de Uso</button>
  </form>

  <form method="post" action="{{ route('logout') }}" style="margin-top:12px;text-align:center">
    @csrf
    <button class="btn ghost small">Sair da conta</button>
  </form>
@endsection
