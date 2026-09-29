@extends('layouts.app')

@section('title', 'Instalação — QEAI One')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="center">
  <div class="card wide">
    <div class="brand"><img src="{{ asset('img/logo.svg') }}" alt="QEAI One"></div>
    <span class="eyebrow">Instalação</span>
    <h1>Vamos preparar o QEAI One.</h1>

    @php
      $steps = ['requirements' => 'Requisitos', 'database' => 'Banco de dados', 'admin' => 'ADM Root', 'identity' => 'Identidade'];
    @endphp
    <div class="stepper">
      @foreach ($steps as $key => $label)
        <span class="step-dot {{ $key === $step ? 'active' : '' }}">{{ $loop->iteration }}. {{ $label }}</span>
      @endforeach
    </div>

    @if ($step === 'requirements')
      @php $allOk = collect($requirements)->every(fn ($r) => $r['ok']); @endphp
      @if (! $allOk)
        <div class="alert err">Alguns requisitos não foram atendidos. Ajuste-os para continuar.</div>
      @endif
      <ul class="req-list">
        @foreach ($requirements as $req)
          <li><span>{{ $req['label'] }} <small>{{ $req['detail'] }}</small></span><span class="{{ $req['ok'] ? 'ok' : 'no' }}">{{ $req['ok'] ? 'OK' : 'Falta' }}</span></li>
        @endforeach
      </ul>
      @if ($allOk)
        <a class="btn" href="{{ route('install.database') }}">Continuar</a>
      @endif

    @elseif ($step === 'database')
      @if ($connected)
        <div class="alert ok">Banco de dados conectado. Você pode confirmar e continuar.</div>
      @endif
      @error('database')<div class="alert err">{{ $message }}</div>@enderror
      <form method="post" action="{{ route('install.database') }}">
        @csrf
        <div class="grid-2">
          <label>Servidor<input name="host" value="{{ old('host', $values['host']) }}" required></label>
          <label>Porta<input name="port" value="{{ old('port', $values['port']) }}" required></label>
          <label>Banco de dados<input name="database" value="{{ old('database', $values['database']) }}" required></label>
          <label>Usuário<input name="username" value="{{ old('username', $values['username']) }}" required></label>
        </div>
        <label>Senha<input type="password" name="password" value=""><small>Deixe vazio para manter a atual.</small></label>
        <button class="btn">Testar, migrar e continuar</button>
      </form>

    @elseif ($step === 'admin')
      @error('email')<div class="alert err">{{ $message }}</div>@enderror
      @error('password')<div class="alert err">{{ $message }}</div>@enderror
      <p class="muted">Crie o acesso principal (ADM Root). Ele gerencia toda a plataforma.</p>
      <form method="post" action="{{ route('install.admin') }}">
        @csrf
        <label>Nome<input name="name" value="{{ old('name', 'ADM') }}" required maxlength="120"></label>
        <label>E-mail<input type="email" name="email" value="{{ old('email') }}" required></label>
        <div class="grid-2">
          <label>Senha<input type="password" name="password" minlength="10" maxlength="72" required><small>Mínimo de 10 caracteres.</small></label>
          <label>Confirme a senha<input type="password" name="password_confirmation" minlength="10" maxlength="72" required></label>
        </div>
        <button class="btn">Criar ADM Root</button>
      </form>

    @elseif ($step === 'identity')
      @if (session('install_message'))
        <div class="alert ok">{{ session('install_message') }}</div>
      @endif
      <p class="muted">Último passo: defina a identidade da plataforma. Você poderá ajustar tudo depois no painel.</p>
      <form method="post" action="{{ route('install.identity') }}">
        @csrf
        <label>Nome da plataforma<input name="company_name" value="{{ old('company_name', $values['company_name']) }}" required maxlength="120"></label>
        <div class="grid-2">
          <label>Razão social<input name="company_legal_name" value="{{ old('company_legal_name', $values['company_legal_name']) }}" maxlength="180"></label>
          <label>CNPJ<input name="company_document" value="{{ old('company_document', $values['company_document']) }}" maxlength="20"></label>
          <label>E-mail de contato<input type="email" name="contact_email" value="{{ old('contact_email', $values['contact_email']) }}"></label>
          <label>Cor principal<input type="color" name="primary_color" value="{{ old('primary_color', $values['primary_color']) }}"></label>
        </div>
        <h2>E-mail da plataforma (opcional)</h2>
        <p class="muted">Usado para redefinição de senha e avisos. Pode configurar depois.</p>
        <div class="grid-2">
          <label>Servidor SMTP<input name="smtp_host" value="{{ old('smtp_host', $values['smtp_host']) }}"></label>
          <label>Porta<input name="smtp_port" value="{{ old('smtp_port', $values['smtp_port']) }}"></label>
          <label>Criptografia<select name="smtp_encryption">
            <option value="ssl" @selected(old('smtp_encryption', $values['smtp_encryption']) === 'ssl')>SSL/TLS (465)</option>
            <option value="tls" @selected(old('smtp_encryption', $values['smtp_encryption']) === 'tls')>STARTTLS (587)</option>
          </select></label>
          <label>Usuário<input type="email" name="smtp_username" value="{{ old('smtp_username', $values['smtp_username']) }}"></label>
          <label>Senha<input type="password" name="smtp_password"></label>
          <label>Nome do remetente<input name="smtp_from_name" value="{{ old('smtp_from_name', $values['smtp_from_name']) }}"></label>
        </div>
        <button class="btn">Concluir instalação</button>
      </form>
    @endif
  </div>
</div>
@endsection
