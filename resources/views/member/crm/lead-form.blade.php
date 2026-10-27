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
    <p class="muted">O ID, a data de criação e o histórico são automáticos. Todos os demais dados são campos do CRM.</p>
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
@else
  <div class="lead-id-banner">
    <span class="muted">ID do lead</span>
    <strong>Gerado automaticamente ao salvar</strong>
    <span class="badge">permanente</span>
    <span class="muted" style="margin-left:auto">Data de criação e histórico também são automáticos</span>
  </div>
@endif

<form method="post" action="{{ $editing ? route('member.org.contacts.update', [$organization, $contact]) : route('member.org.contacts.store', $organization) }}">
  @csrf
  @if ($editing) @method('PUT') @endif

  <div class="card">
    <div class="card-head">
      <div>
        <h2>Campos do lead</h2>
        <p class="muted">Preencha os campos configurados pela sua organização. Nenhum deles é obrigatório pelo sistema.</p>
      </div>
      @if (\App\Support\OrgAccess::canData(auth()->user(), $organization, 'org.crm_fields'))
        <a class="btn secondary small" href="{{ route('member.org.fields.index', $organization) }}"><x-icon name="settings" :size="15"/> Gerenciar campos</a>
      @endif
    </div>

    <div class="grid-2">
      @if ($fields->isEmpty())
        <div class="full empty-state" style="padding:24px">
          <x-icon name="list-check" :size="26"/>
          <p class="muted" style="margin:8px 0 0">Nenhum campo ativo. Crie ou reative campos na Gestão de CRM para preencher este lead.</p>
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
