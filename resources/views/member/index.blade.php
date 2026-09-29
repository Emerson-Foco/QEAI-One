@extends('layouts.member')

@section('title', 'Minhas organizações — QEAI One')

@section('panel')
<span class="pill">Membro</span>
<h1>Minhas organizações</h1>
<p class="muted">Estas são as organizações das quais você participa. Você pode ter grupos de acesso diferentes em cada uma.</p>

@forelse ($memberships as $membership)
  <div class="card">
    <h2>{{ $membership->organization->name }}</h2>
    <p class="muted">
      Grupo: <strong>{{ $membership->role?->name ?? '—' }}</strong>
      · Plano: {{ $membership->organization->plan?->name ?? '—' }}
    </p>
    <a class="btn" href="{{ route('member.org.show', $membership->organization) }}">Abrir organização</a>
  </div>
@empty
  <div class="card"><p class="muted">Você ainda não participa de nenhuma organização.</p></div>
@endforelse
@endsection
