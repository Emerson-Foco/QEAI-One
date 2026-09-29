@extends('layouts.panel')

@section('title', 'Organizações — QEAI One')

@section('panel')
<div class="page-head">
  <div>
    <span class="eyebrow">Plataforma</span>
    <h1>Organizações</h1>
    <p class="muted">Cada organização é um espaço isolado, com os próprios membros e grupos de acesso.</p>
  </div>
</div>

<div class="card">
  <h2>Nova organização</h2>
  <form method="post" action="{{ route('panel.organizations.store') }}" class="grid-2">
    @csrf
    <label>Nome<input name="name" value="{{ old('name') }}" required maxlength="160"></label>
    <label>CNPJ (opcional)<input name="document" value="{{ old('document') }}" maxlength="24"></label>
    <div class="full"><button class="btn">Criar organização</button></div>
  </form>
</div>

<div class="card">
  <h2>Organizações cadastradas</h2>
  <table class="table">
    <thead>
      <tr><th>Nome</th><th>Documento</th><th>Membros</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      @forelse ($organizations as $organization)
        <tr>
          <td><strong>{{ $organization->name }}</strong><br><small class="muted">{{ $organization->slug }}</small></td>
          <td>{{ $organization->document ?: '—' }}</td>
          <td>{{ $organization->memberships_count }}</td>
          <td><span class="badge">{{ $organization->status }}</span></td>
          <td class="right">
            <div class="row-actions inline">
              <a class="btn secondary small" href="{{ route('member.org.show', $organization) }}">Painel</a>
              <a class="btn secondary small" href="{{ route('panel.organizations.show', $organization) }}">Detalhes</a>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted">Nenhuma organização cadastrada ainda.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
