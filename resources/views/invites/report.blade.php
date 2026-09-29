@extends('layouts.app')

@section('title', 'Não reconheço este convite — QEAI One')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="center">
  <div class="card narrow">
    <div class="brand"><img src="{{ asset('img/logo.svg') }}" alt="QEAI One"></div>
    <span class="eyebrow">Segurança</span>
    <h1>Não reconhece este convite?</h1>
    <p class="muted">
      Se você não esperava este convite, confirme abaixo. Vamos registrá-lo para análise e bloquear o convite.
      Nenhuma conta será criada e nenhum dado seu será usado.
    </p>

    @if ($invite)
      <ul class="req-list">
        <li><span>Organização</span><strong>{{ $invite->organization?->name ?? '—' }}</strong></li>
        <li><span>E-mail convidado</span><strong>{{ $invite->email }}</strong></li>
        <li><span>Enviado em</span><strong>{{ $invite->created_at?->format('d/m/Y H:i') }}</strong></li>
      </ul>
      <form method="post" action="{{ route('invite.report', $token) }}">
        @csrf
        <label>Opcional: conte o que aconteceu<input name="note" maxlength="255" placeholder="Ex.: não conheço esta empresa"></label>
        <button class="btn full">Confirmar que não reconheço</button>
      </form>
    @else
      <div class="alert err">Este link não corresponde a um convite ativo.</div>
      <a class="btn secondary" href="{{ route('home') }}">Voltar ao início</a>
    @endif
  </div>
</div>
@endsection
