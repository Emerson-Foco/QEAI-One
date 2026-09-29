@extends('layouts.app')

@section('title', 'Chat — ' . ($organization->name ?? 'QEAI One'))
@section('robots', 'noindex,nofollow')

@section('content')
<div class="center">
  <div class="card narrow">
    <div class="brand"><img src="{{ asset('img/logo.svg') }}" alt="{{ $organization->name ?? 'QEAI One' }}"></div>
    <h1>{{ $channel->name }}</h1>
    <p class="muted">Fale com a gente. Informe nome/e-mail (opcional) e sua mensagem.</p>

    @if (session('chat_sent'))
      <div class="alert ok">Mensagem enviada. Responderemos por aqui.</div>
    @endif
    @if ($errors->any())
      <div class="alert err">{{ $errors->first() }}</div>
    @endif

    @if ($messages->isNotEmpty())
      <div class="chat-log">
        @foreach ($messages as $message)
          <div class="chat-msg {{ $message->direction === 'out' ? 'out' : 'in' }}">
            <div class="chat-bubble">{{ $message->body }}</div>
            <small class="muted">{{ $message->created_at?->format('d/m H:i') }}</small>
          </div>
        @endforeach
      </div>
    @endif

    <form method="post" action="{{ route('chat.send', $channel->token) }}">
      @csrf
      <div class="grid-2">
        <label>Nome (opcional)<input name="name" maxlength="120"></label>
        <label>E-mail (opcional)<input type="email" name="email" maxlength="180"></label>
      </div>
      <label>Mensagem<textarea name="body" rows="3" required maxlength="4000"></textarea></label>
      <button class="btn full">Enviar</button>
    </form>
  </div>
</div>
@endsection
