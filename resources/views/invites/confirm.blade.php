@extends('layouts.app')

@section('title', 'Aceitar convite — QEAI One')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="center">
  <div class="card narrow">
    <div class="brand"><img src="{{ asset('img/logo.svg') }}" alt="QEAI One"></div>
    <span class="eyebrow">Convite</span>
    <h1>Aceitar convite para {{ $invite->organization->name }}</h1>
    <p class="muted">
      Você está conectado como <strong>{{ $invite->email }}</strong>. Grupo oferecido:
      <strong>{{ $invite->role?->name ?? 'Membro' }}</strong>.
    </p>

    @if ($alreadyMember)
      <div class="alert ok">Você já participa desta organização. Aceitar atualizará seu grupo para o convidado.</div>
    @endif

    <form method="post" action="{{ url('/convite/' . $token) }}">
      @csrf
      <button class="btn full">Aceitar convite</button>
    </form>

    <p class="muted" style="margin-top:18px;font-size:.78rem">
      Não reconhece este convite? <a href="{{ route('invite.report.form', $token) }}">Informe aqui</a>.
    </p>
  </div>
</div>
@endsection
