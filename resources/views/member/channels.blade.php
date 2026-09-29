@extends('layouts.member')

@section('title', 'Canais — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <span class="pill">Atendimento</span>
    <h1>Canais</h1>
    <p class="muted">Canais de conversa desta organização.</p>
  </div>
</div>

@if (session('new_chat_url'))
  <div class="alert ok">
    Chat criado. Link público: <code style="word-break:break-all">{{ session('new_chat_url') }}</code><br>
    <small>Incorpore no site: <code>&lt;iframe src="{{ session('new_chat_url') }}" style="width:100%;height:600px;border:0"&gt;&lt;/iframe&gt;</code></small>
  </div>
@endif

<div class="card">
  <h2>Novo canal de chat no site</h2>
  <form method="post" action="{{ route('member.org.channels.chat.store', $organization) }}" class="inline-form">
    @csrf
    <input name="name" placeholder="Chat do site" required maxlength="120">
    <button class="btn">Criar chat</button>
  </form>
</div>

<div class="card">
  <h2>Novo canal de e-mail</h2>
  <p class="muted">Use o SMTP da caixa de atendimento da organização.</p>
  <form method="post" action="{{ route('member.org.channels.email.store', $organization) }}">
    @csrf
    <div class="grid-2">
      <label>Nome<input name="name" required maxlength="120" placeholder="Atendimento"></label>
      <label>Servidor SMTP<input name="host" required maxlength="255" placeholder="smtp.hostinger.com"></label>
      <label>Porta<input type="number" name="port" value="465" required min="1" max="65535"></label>
      <label>Criptografia<select name="encryption"><option value="ssl">SSL/TLS (465)</option><option value="tls">STARTTLS (587)</option></select></label>
      <label>Usuário (e-mail)<input type="email" name="username" required maxlength="180"></label>
      <label>Senha<input type="password" name="password" required maxlength="255"></label>
      <label>Nome do remetente<input name="from_name" maxlength="100"></label>
      <label>E-mail do remetente<input type="email" name="from_email" maxlength="180"></label>
    </div>
    <button class="btn">Criar canal de e-mail</button>
  </form>
</div>

<div class="card">
  <table class="table">
    <thead><tr><th>Nome</th><th>Tipo</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @forelse ($channels as $channel)
        <tr>
          <td>
            {{ $channel->name }}
            @if ($channel->type === 'web_chat' && $channel->token)<br><small class="muted">{{ url('/chat/' . $channel->token) }}</small>@endif
          </td>
          <td>{{ $channel->type }}</td>
          <td>{{ $channel->is_active ? 'Ativo' : 'Inativo' }}</td>
          <td class="right">
            <div class="row-actions inline">
              <form method="post" action="{{ route('member.org.channels.toggle', [$organization, $channel]) }}">@csrf<button class="btn secondary small">{{ $channel->is_active ? 'Desativar' : 'Ativar' }}</button></form>
              <form method="post" action="{{ route('member.org.channels.destroy', [$organization, $channel]) }}" onsubmit="return confirm('Remover canal?');">@csrf @method('DELETE')<button class="btn secondary small">Excluir</button></form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="4" class="muted">Nenhum canal.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
