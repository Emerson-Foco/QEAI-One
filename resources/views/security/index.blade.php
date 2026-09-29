@extends('layouts.panel')

@section('title', 'Segurança — QEAI One')

@section('panel')
<div class="page-head">
  <div>
    <span class="eyebrow">Conta</span>
    <h1>Segurança</h1>
    <p class="muted">Senha, verificação em duas etapas e situação do seu e-mail.</p>
  </div>
</div>

<div class="card">
  <h2>Senha</h2>
  <form method="post" action="{{ route('panel.security.password') }}">
    @csrf @method('PUT')
    <div class="grid-2">
      <label>Senha atual<input type="password" name="current_password" required autocomplete="current-password"></label>
      <label>Nova senha<input type="password" name="password" minlength="10" maxlength="72" required autocomplete="new-password"></label>
      <label>Confirme a nova senha<input type="password" name="password_confirmation" minlength="10" maxlength="72" required></label>
    </div>
    <button class="btn secondary">Atualizar senha</button>
  </form>
</div>

<div class="card">
  <h2>Verificação em duas etapas (2FA)</h2>
  @if ($user->two_factor_confirmed_at)
    <p class="muted">Situação: <span class="badge">ativa</span></p>
    <form method="post" action="{{ route('panel.security.2fa.disable') }}">
      @csrf
      <label>Senha atual<input type="password" name="current_password" required></label>
      <button class="btn secondary">Desativar 2FA</button>
    </form>
  @elseif ($pendingSecret)
    <p class="muted">1) Adicione a conta no app autenticador usando o segredo abaixo. 2) Informe o código para confirmar.</p>
    <label>Segredo<code class="secret-box">{{ $pendingSecret }}</code></label>
    <label>URI (opcional)<code class="secret-box">{{ $pendingUri }}</code></label>
    <form method="post" action="{{ route('panel.security.2fa.confirm') }}">
      @csrf
      <label>Código do app<input name="code" inputmode="numeric" maxlength="6" required></label>
      <button class="btn">Confirmar e ativar</button>
    </form>
  @else
    <p class="muted">Situação: <span class="badge">desativada</span>. Recomendado para o ADM Root.</p>
    <form method="post" action="{{ route('panel.security.2fa.start') }}">
      @csrf
      <button class="btn">Ativar 2FA</button>
    </form>
  @endif
</div>

<div class="card">
  <h2>E-mail</h2>
  <p class="muted">{{ $user->email }} — <strong>{{ $user->email_verified_at ? 'verificado' : 'não verificado' }}</strong></p>
  @unless ($user->email_verified_at)
    <form method="post" action="{{ route('verification.send') }}">
      @csrf
      <button class="btn secondary">Reenviar verificação</button>
    </form>
  @endunless
</div>
@endsection
