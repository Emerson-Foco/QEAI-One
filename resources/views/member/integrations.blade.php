@extends('layouts.member')

@section('title', 'Integrações — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <span class="pill">Integrações</span>
    <h1>Integrações</h1>
    <p class="muted">Chaves de API (entrada de leads) e webhooks (acionam suas ferramentas, tipo N8N).</p>
  </div>
</div>

@if (session('new_api_key'))
  <div class="alert ok">
    Chave criada — copie agora (não será exibida novamente):<br>
    <code style="word-break:break-all">{{ session('new_api_key') }}</code><br>
    <small>Endpoint: <code>{{ url('/api/v1/leads') }}</code> · cabeçalho <code>X-Api-Key</code> · JSON.</small>
  </div>
@endif

<div class="card">
  <h2>Chaves de API</h2>
  <form method="post" action="{{ route('member.org.integrations.keys.store', $organization) }}" class="inline-form">
    @csrf
    <input name="name" placeholder="Nome (ex.: Site, N8N)" required maxlength="120">
    <button class="btn">Criar chave</button>
  </form>
  <table class="table">
    <thead><tr><th>Nome</th><th>Prefixo</th><th>Último uso</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @forelse ($keys as $key)
        <tr>
          <td>{{ $key->name }}</td>
          <td><code>{{ $key->prefix }}…</code></td>
          <td>{{ $key->last_used_at?->format('d/m/Y H:i') ?? '—' }}</td>
          <td>{{ $key->revoked_at ? 'Revogada' : 'Ativa' }}</td>
          <td class="right">
            @unless ($key->revoked_at)
              <form method="post" action="{{ route('member.org.integrations.keys.revoke', [$organization, $key]) }}" onsubmit="return confirm('Revogar chave?');">@csrf<button class="btn secondary small">Revogar</button></form>
            @endunless
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted">Nenhuma chave.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Webhooks (triggers)</h2>
  <p class="muted">Ao criar um lead, enviamos um POST assinado (HMAC-SHA256) para a URL — ideal para N8N/Zapier/Make. Use o segredo para validar.</p>
  <form method="post" action="{{ route('member.org.integrations.webhooks.store', $organization) }}">
    @csrf
    <div class="grid-2">
      <label class="full">URL<input type="url" name="url" required maxlength="255" placeholder="https://n8n.suaempresa.com/webhook/..."></label>
      <label class="full">Eventos
        <span class="checks">
          @foreach ($events as $key => $label)
            <label class="check"><input type="checkbox" name="events[]" value="{{ $key }}" checked> {{ $label }} <code>{{ $key }}</code></label>
          @endforeach
        </span>
      </label>
    </div>
    <button class="btn">Criar webhook</button>
  </form>
  <table class="table">
    <thead><tr><th>URL</th><th>Eventos</th><th>Último envio</th><th></th></tr></thead>
    <tbody>
      @forelse ($webhooks as $webhook)
        <tr>
          <td style="word-break:break-all">{{ $webhook->url }}<br><small class="muted">segredo: {{ $webhook->secret }}</small></td>
          <td>{{ implode(', ', $webhook->events ?? []) }}</td>
          <td>{{ $webhook->last_called_at ? (($webhook->last_status ?: 'falha') . ' em ' . $webhook->last_called_at->format('d/m H:i')) : '—' }}</td>
          <td class="right">
            <div class="row-actions inline">
              <form method="post" action="{{ route('member.org.integrations.webhooks.toggle', [$organization, $webhook]) }}">@csrf<button class="btn secondary small">{{ $webhook->is_active ? 'Desativar' : 'Ativar' }}</button></form>
              <form method="post" action="{{ route('member.org.integrations.webhooks.destroy', [$organization, $webhook]) }}" onsubmit="return confirm('Remover webhook?');">@csrf @method('DELETE')<button class="btn secondary small">Excluir</button></form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="4" class="muted">Nenhum webhook.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
