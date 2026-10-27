@extends('layouts.member')

@section('title', ($contact ? 'Editar lead' : 'Novo lead') . ' — ' . $organization->name)

@section('panel')
@php
  $editing = $contact !== null;
  $values = $editing ? ($contact->custom ?? []) : [];
@endphp

<div class="page-header">
  <div>
    <span class="page-kicker">CRM · Leads</span>
    <h1>{{ $editing ? 'Editar lead' : 'Novo lead' }}</h1>
    <p class="muted">{{ $editing ? 'Os dados ficam vinculados ao ID imutável deste lead.' : 'Preencha os dados abaixo. O ID do lead será gerado automaticamente.' }}</p>
  </div>
  <div class="page-actions">
    <a class="btn secondary" href="{{ $editing ? route('member.org.contacts.show', [$organization, $contact]) : route('member.org.contacts.index', $organization) }}">Cancelar</a>
  </div>
</div>

@if ($editing)
  <div class="lead-id-banner">
    <span class="muted">ID do lead</span>
    <strong class="lead-id">{{ $contact->lead_id }}</strong>
    <span class="badge">permanente</span>
    <span class="muted" style="margin-left:auto">Criado em {{ $contact->created_at?->format('d/m/Y H:i') }}</span>
  </div>
@endif

<form method="post" action="{{ $editing ? route('member.org.contacts.update', [$organization, $contact]) : route('member.org.contacts.store', $organization) }}">
  @csrf
  @if ($editing) @method('PUT') @endif

  <div class="card">
    <div class="card-head">
      <div>
        <h2>Dados principais</h2>
        <p class="muted">Informações fixas de identificação do lead.</p>
      </div>
    </div>

    <div class="grid-2">
      <label>Nome do lead
        <input name="name" value="{{ old('name', $contact?->name) }}" required maxlength="180" placeholder="Nome da pessoa ou contato" autofocus>
      </label>
      <label>E-mail
        <input type="email" name="email" value="{{ old('email', $contact?->email) }}" maxlength="180" placeholder="email@exemplo.com">
      </label>
      <label>Telefone
        <input name="phone" value="{{ old('phone', $contact?->phone) }}" maxlength="40" placeholder="(11) 99999-9999">
      </label>
      <label>WhatsApp
        <input name="whatsapp" value="{{ old('whatsapp', $contact?->whatsapp) }}" maxlength="40" placeholder="(11) 99999-9999">
      </label>
      <label>Origem
        <input name="source" value="{{ old('source', $contact?->source) }}" maxlength="60" placeholder="Site, indicação, anúncio…">
      </label>
      <label>Observações
        <textarea name="notes" rows="3" maxlength="5000" placeholder="Anotações internas sobre o lead">{{ old('notes', $contact?->notes) }}</textarea>
      </label>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <div>
        <h2>Campos do CRM</h2>
        <p class="muted">Campos criados pela sua organização para este tipo de lead.</p>
      </div>
      @if (\App\Support\OrgAccess::canData(auth()->user(), $organization, 'org.crm_fields'))
        <a class="btn secondary small" href="{{ route('member.org.fields.index', $organization) }}"><x-icon name="settings" :size="15"/> Gerenciar campos</a>
      @endif
    </div>

    <div class="grid-2">
      @if ($fields->isEmpty())
        <div class="full empty-state" style="padding:22px">
          <p class="muted" style="margin:0">Nenhum campo ativo no momento. Ative ou crie campos na Gestão de CRM.</p>
        </div>
      @else
        @include('member.crm.partials.field-inputs', ['fields' => $fields, 'values' => $values])
      @endif
    </div>
  </div>

  <div class="save-actions">
    <button class="btn">{{ $editing ? 'Salvar alterações' : 'Criar lead' }} <x-icon name="arrow-right" :size="16"/></button>
    <a class="btn secondary" href="{{ $editing ? route('member.org.contacts.show', [$organization, $contact]) : route('member.org.contacts.index', $organization) }}">Cancelar</a>
  </div>
</form>
@endsection
