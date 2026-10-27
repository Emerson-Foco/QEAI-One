@extends('layouts.member')

@section('title', 'Leads — ' . $organization->name)

@section('panel')
<div class="page-header">
  <div>
    <span class="page-kicker">CRM</span>
    <h1>Leads</h1>
    <p class="muted">Todos os leads da organização, com histórico e campos personalizados.</p>
  </div>
  <div class="page-actions">
    @if (\App\Support\OrgAccess::canData(auth()->user(), $organization, 'org.crm_fields'))
      <a class="btn secondary" href="{{ route('member.org.fields.index', $organization) }}">
        <x-icon name="settings" :size="16"/> Gestão de CRM
      </a>
    @endif
    <a class="btn" href="{{ route('member.org.contacts.create', $organization) }}">
      <x-icon name="plus" :size="16"/> Novo lead
    </a>
  </div>
</div>

<div class="lead-summary">
  <div><span class="muted">Total de leads</span><strong>{{ number_format($total, 0, ',', '.') }}</strong></div>
  <div><span class="muted">Novos este mês</span><strong>{{ number_format($newThisMonth, 0, ',', '.') }}</strong></div>
  <div><span class="muted">Campos ativos</span><strong>{{ $fields->count() }}</strong></div>
</div>

<div class="card">
  <form method="get" class="inline-form" style="margin:0">
    <input name="q" value="{{ $search }}" placeholder="Buscar por ID do lead, nome, e-mail ou telefone" aria-label="Buscar leads" style="min-width:320px;flex:1">
    <button class="btn secondary"><x-icon name="search" :size="16"/> Buscar</button>
    @if ($search !== '')
      <a class="btn ghost" href="{{ route('member.org.contacts.index', $organization) }}">Limpar</a>
    @endif
  </form>
</div>

<div class="card">
  @if ($contacts->isEmpty())
    <div class="empty-state">
      <x-icon name="users" :size="30"/>
      <h2 style="margin-top:8px">{{ $search !== '' ? 'Nenhum lead encontrado' : 'Nenhum lead cadastrado ainda' }}</h2>
      <p class="muted">{{ $search !== '' ? 'Tente outro termo de busca.' : 'Cadastre o primeiro lead para começar a acompanhar sua operação.' }}</p>
      @if ($search === '')
        <a class="btn" href="{{ route('member.org.contacts.create', $organization) }}"><x-icon name="plus" :size="16"/> Cadastrar primeiro lead</a>
      @endif
    </div>
  @else
    <div class="lead-list">
      @foreach ($contacts as $contact)
        @php
          $initials = collect(explode(' ', (string) $contact->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: 'L';
        @endphp
        <article class="lead-item">
          <div class="lead-item-main">
            <span class="avatar sm">{{ $initials }}</span>
            <div style="min-width:0">
              <a class="lead-name" href="{{ route('member.org.contacts.show', [$organization, $contact]) }}">{{ $contact->name }}</a>
              <div class="lead-item-contact">{{ $contact->email ?: 'sem e-mail' }}</div>
              @if ($contact->phone || $contact->whatsapp)
                <div class="lead-item-contact">{{ $contact->phone ?: $contact->whatsapp }}</div>
              @endif
              <span class="lead-id" title="{{ $contact->lead_id }}">{{ $contact->lead_id }}</span>
            </div>
          </div>

          <div class="lead-item-fields">
            @foreach ($listFields as $field)
              @php $value = $contact->custom[$field->key] ?? null; @endphp
              <span class="lead-chip">
                <small>{{ $field->label }}</small>
                @if ($field->type === 'boolean')
                  {{ $value ? 'Sim' : 'Não' }}
                @else
                  {{ filled($value) ? $value : '—' }}
                @endif
              </span>
            @endforeach
          </div>

          <div class="lead-item-side">
            <span class="muted nowrap">{{ $contact->created_at?->format('d/m/Y') }}</span>
            <div class="row-actions inline">
              <a class="btn secondary small" href="{{ route('member.org.contacts.show', [$organization, $contact]) }}">Ver</a>
              <a class="btn secondary small" href="{{ route('member.org.contacts.edit', [$organization, $contact]) }}">Editar</a>
            </div>
          </div>
        </article>
      @endforeach
    </div>

    {{ $contacts->links() }}
  @endif
</div>
@endsection
