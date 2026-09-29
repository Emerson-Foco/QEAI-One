@extends('layouts.member')

@section('title', 'Conversa — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <a class="muted" href="{{ route('member.org.inbox.index', $organization) }}">← Atendimento</a>
    <h1>{{ $conversation->contact?->name ?? 'Visitante' }}</h1>
    <p class="muted">{{ $conversation->channel?->name }} · status {{ $conversation->status }} · responsável {{ $conversation->assignee?->name ?? '—' }}</p>
  </div>
  @if ($conversation->contact)
    <a class="btn secondary" href="{{ route('member.org.contacts.edit', [$organization, $conversation->contact]) }}">Ver contato</a>
  @endif
</div>

<div class="card">
  <div class="chat-log">
    @forelse ($conversation->messages as $message)
      <div class="chat-msg {{ $message->direction === 'out' ? 'out' : 'in' }}">
        <div class="chat-bubble">{{ $message->body }}</div>
        <small class="muted">
          {{ $message->created_at?->format('d/m H:i') }}
          @if ($message->author) · {{ $message->author->name }} @endif
          @if (! empty($message->meta['error'])) · falha no e-mail @endif
        </small>
      </div>
    @empty
      <p class="muted">Sem mensagens.</p>
    @endforelse
  </div>

  <form method="post" action="{{ route('member.org.inbox.reply', [$organization, $conversation]) }}">
    @csrf
    <label class="full">Resposta<textarea name="body" rows="3" required maxlength="4000"></textarea></label>
    <button class="btn">Responder</button>
  </form>

  <div class="grid-2" style="margin-top:18px">
    <form method="post" action="{{ route('member.org.inbox.status', [$organization, $conversation]) }}" class="inline-form">
      @csrf
      <select name="status">
        <option value="open" @selected($conversation->status === 'open')>Aberta</option>
        <option value="pending" @selected($conversation->status === 'pending')>Pendente</option>
        <option value="closed" @selected($conversation->status === 'closed')>Fechada</option>
      </select>
      <button class="btn secondary">Mudar status</button>
    </form>
    <form method="post" action="{{ route('member.org.inbox.assign', [$organization, $conversation]) }}" class="inline-form">
      @csrf
      <select name="assigned_to">
        <option value="">— sem responsável —</option>
        @foreach ($members as $member)
          <option value="{{ $member->id }}" @selected($conversation->assigned_to === $member->id)>{{ $member->name }}</option>
        @endforeach
      </select>
      <button class="btn secondary">Atribuir</button>
    </form>
  </div>
</div>
@endsection
