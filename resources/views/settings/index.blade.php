@extends('layouts.panel')

@section('title', 'Configurações — QEAI One')

@section('panel')
<div class="page-head">
  <div>
    <span class="eyebrow">Plataforma</span>
    <h1>Configurações</h1>
    <p class="muted">Identidade (white label) e e-mail da plataforma. Usado em convites, avisos e redefinição de senha.</p>
  </div>
</div>

<form method="post" action="{{ route('panel.settings.update') }}">
  @csrf @method('PUT')

  <div class="card">
    <h2>Identidade</h2>
    <div class="grid-2">
      <label>Nome da plataforma<input name="company_name" value="{{ old('company_name', $settings['company_name']) }}" required maxlength="120"></label>
      <label>Razão social<input name="company_legal_name" value="{{ old('company_legal_name', $settings['company_legal_name']) }}" maxlength="180"></label>
      <label>CNPJ / documento<input name="company_document" value="{{ old('company_document', $settings['company_document']) }}" maxlength="24"></label>
      <label>E-mail de contato<input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}"></label>
      <label>Cor principal<input type="color" name="primary_color" value="{{ old('primary_color', $settings['primary_color']) }}"></label>
    </div>
  </div>

  <div class="card">
    <h2>E-mail da plataforma (SMTP)</h2>
    <p class="muted">
      Situação:
      @if ($settings['smtp_configured'])
        <span class="badge">configurado</span>
      @else
        <span class="badge">não configurado</span> — convites ficam como link para copiar.
      @endif
    </p>
    <div class="grid-2">
      <label>Servidor<input name="smtp_host" value="{{ old('smtp_host', $settings['smtp_host']) }}" placeholder="smtp.hostinger.com"></label>
      <label>Porta<input type="number" name="smtp_port" value="{{ old('smtp_port', $settings['smtp_port']) }}" min="1" max="65535"></label>
      <label>Criptografia<select name="smtp_encryption">
        <option value="ssl" @selected(old('smtp_encryption', $settings['smtp_encryption']) === 'ssl')>SSL/TLS (465)</option>
        <option value="tls" @selected(old('smtp_encryption', $settings['smtp_encryption']) === 'tls')>STARTTLS (587)</option>
      </select></label>
      <label>Usuário (e-mail completo)<input type="email" name="smtp_username" value="{{ old('smtp_username', $settings['smtp_username']) }}"></label>
      <label>Senha<input type="password" name="smtp_password" placeholder="Deixe vazio para manter"><small>Fica cifrada no banco.</small></label>
      <label>Nome do remetente<input name="smtp_from_name" value="{{ old('smtp_from_name', $settings['smtp_from_name']) }}" maxlength="100"></label>
    </div>
  </div>

  <div class="save-bar" style="display:flex;justify-content:space-between;gap:16px;align-items:center;margin-bottom:24px">
    <span class="muted">As alterações são aplicadas ao salvar.</span>
    <button class="btn">Salvar configurações</button>
  </div>
</form>

<div class="card">
  <h2>Teste de entrega</h2>
  <p class="muted">Envie uma mensagem de teste para confirmar o SMTP.</p>
  <form method="post" action="{{ route('panel.settings.test') }}" class="inline-form">
    @csrf
    <input type="email" name="to" value="{{ old('to', $settings['contact_email']) }}" placeholder="voce@empresa.com" required>
    <button class="btn secondary">Enviar e-mail de teste</button>
  </form>
</div>
@endsection
