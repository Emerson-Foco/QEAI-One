@extends('layouts.app')

@section('title', 'Convite — QEAI One')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="center">
  <div class="card narrow">
    <div class="brand"><img src="{{ asset('img/logo.svg') }}" alt="QEAI One"></div>
    <span class="eyebrow">Convite</span>
    <h1>Este convite não está mais válido.</h1>
    <p class="muted">Ele pode ter sido usado, revogado ou expirado. Se você esperava um convite, peça à organização que envie um novo.</p>
    <a class="btn secondary" href="{{ route('home') }}">Voltar ao início</a>
  </div>
</div>
@endsection
