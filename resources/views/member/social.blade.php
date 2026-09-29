@extends('layouts.member')

@section('title', 'Social — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <span class="pill">Marketing</span>
    <h1>Redes sociais</h1>
    <p class="muted">Agende publicações e dispare para suas contas (webhook de automação ou página do Facebook).</p>
  </div>
</div>

<div class="card">
  <h2>Adicionar conta</h2>
  <form method="post" action="{{ route('member.org.social.accounts.store', $organization) }}">
    @csrf
    <div class="grid-2">
      <label>Nome<input name="name" required maxlength="120" placeholder="Instagram da marca"></label>
      <label>Rede<select name="network">@foreach ($networks as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
      <label class="full">URL do webhook (para redes via automação)<input type="url" name="webhook_url" maxlength="255" placeholder="https://n8n.suaempresa.com/webhook/publicar"></label>
      <label>Page ID (Facebook)<input name="page_id" maxlength="120"></label>
      <label>Access token (Facebook)<input name="access_token" maxlength="2000"></label>
    </div>
    <button class="btn">Adicionar conta</button>
  </form>
  <table class="table">
    <thead><tr><th>Nome</th><th>Rede</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @forelse ($accounts as $account)
        <tr>
          <td>{{ $account->name }}</td>
          <td>{{ $networks[$account->network] ?? $account->network }}</td>
          <td>{{ $account->is_active ? 'Ativa' : 'Inativa' }}</td>
          <td class="right"><div class="row-actions inline">
            <form method="post" action="{{ route('member.org.social.accounts.toggle', [$organization, $account]) }}">@csrf<button class="btn secondary small">{{ $account->is_active ? 'Desativar' : 'Ativar' }}</button></form>
            <form method="post" action="{{ route('member.org.social.accounts.destroy', [$organization, $account]) }}" onsubmit="return confirm('Remover conta?');">@csrf @method('DELETE')<button class="btn secondary small">Excluir</button></form>
          </div></td>
        </tr>
      @empty
        <tr><td colspan="4" class="muted">Nenhuma conta social.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Nova publicação</h2>
  <form method="post" action="{{ route('member.org.social.posts.store', $organization) }}">
    @csrf
    <div class="grid-2">
      <label>Título<input name="title" required maxlength="180"></label>
      <label>URL da mídia (opcional)<input type="url" name="media_url" maxlength="500"></label>
      <label class="full">Conteúdo<textarea name="body" rows="4" required maxlength="10000"></textarea></label>
      <label>Agendar para<input type="datetime-local" name="scheduled_at"><small>Vazio = rascunho.</small></label>
      <label class="full">Contas
        <span class="checks">
          @foreach ($accounts as $account)
            <label class="check"><input type="checkbox" name="targets[]" value="{{ $account->id }}"> {{ $account->name }} ({{ $account->network }})</label>
          @endforeach
        </span>
      </label>
    </div>
    <button class="btn">Salvar publicação</button>
  </form>
</div>

<div class="card">
  <h2>Publicações</h2>
  <table class="table">
    <thead><tr><th>Título</th><th>Status</th><th>Agendado</th><th>Destinos</th><th></th></tr></thead>
    <tbody>
      @forelse ($posts as $post)
        <tr>
          <td><strong>{{ $post->title }}</strong><br><small class="muted">{{ \Illuminate\Support\Str::limit($post->body, 80) }}</small></td>
          <td><span class="badge">{{ $post->status }}</span></td>
          <td>{{ $post->scheduled_at?->format('d/m/Y H:i') ?? '—' }}</td>
          <td>
            @foreach ($post->targets as $target)
              <div><small class="muted">{{ $target->account?->name ?? '—' }}: {{ $target->status }}@if ($target->error) — {{ \Illuminate\Support\Str::limit($target->error, 60) }}@endif</small></div>
            @endforeach
          </td>
          <td class="right"><div class="row-actions inline">
            <form method="post" action="{{ route('member.org.social.posts.publish', [$organization, $post]) }}">@csrf<button class="btn secondary small">Publicar agora</button></form>
            <form method="post" action="{{ route('member.org.social.posts.destroy', [$organization, $post]) }}" onsubmit="return confirm('Remover publicação?');">@csrf @method('DELETE')<button class="btn secondary small">Excluir</button></form>
          </div></td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted">Nenhuma publicação.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
