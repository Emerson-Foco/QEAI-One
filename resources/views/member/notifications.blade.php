@extends('layouts.member')

@section('title', 'Notificações — QEAI One')

@section('panel')
<div class="page-head">
  <div>
    <span class="pill">Notificações</span>
    <h1>Notificações</h1>
  </div>
  <form method="post" action="{{ route('member.notifications.readAll') }}">
    @csrf
    <button class="btn secondary">Marcar todas como lidas</button>
  </form>
</div>

<div class="card">
  <table class="table">
    <thead><tr><th></th><th>Quando</th><th>Notificação</th><th></th></tr></thead>
    <tbody>
      @forelse ($notifications as $notification)
        <tr class="{{ $notification->isRead() ? '' : 'unread-row' }}">
          <td>{{ $notification->isRead() ? '' : '●' }}</td>
          <td>{{ $notification->created_at?->format('d/m/Y H:i') }}</td>
          <td><strong>{{ $notification->title }}</strong>@if ($notification->body)<br><small class="muted">{{ $notification->body }}</small>@endif</td>
          <td class="right">
            <form method="post" action="{{ route('member.notifications.read', $notification) }}">
              @csrf
              <button class="btn secondary small">{{ $notification->url ? 'Abrir' : 'Marcar lida' }}</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="4" class="muted">Nenhuma notificação.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
