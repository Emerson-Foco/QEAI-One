@extends('layouts.panel')

@section('title', 'Painel — QEAI One')

@section('panel')
<span class="pill">Root Admin</span>
<h1>Olá, {{ auth()->user()->name }}.</h1>
<p class="muted">Esta é a base do QEAI One. Organizações, grupos de acesso, planos, convites e auditoria já estão ativos.</p>

@if (($reportedInvites ?? 0) > 0)
  <div class="alert err">
    <strong>{{ $reportedInvites }}</strong> convite(s) reportado(s) como não reconhecido(s).
    <a href="{{ route('panel.logs.index', ['action' => 'invite.reported']) }}">Revisar agora</a>.
  </div>
@endif

<div class="card">
  <h2>Próximos passos da fundação</h2>
  <ul>
    <li>Painel da organização com permissões aplicadas</li>
    <li>2FA e recuperação de senha por e-mail</li>
    <li>Deploy em produção (one.qeai.com.br)</li>
  </ul>
</div>
@endsection
