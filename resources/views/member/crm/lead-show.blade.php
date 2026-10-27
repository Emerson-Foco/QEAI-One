@extends('layouts.member')

@section('title', $contact->name . ' — ' . $organization->name)

@section('panel')
<div class="page-header">
  <div>
    <span class="page-kicker">CRM · Lead</span>
    <h1>{{ $contact->name }}</h1>
    <p class="muted">ID permanente <strong class="lead-id">{{ $contact->lead_id }}</strong> · criado em {{ $contact->created_at?->format('d/m/Y H:i') }}</p>
  </div>
  <div class="page-actions">
    <a class="btn secondary" href="{{ route('member.org.contacts.index', $organization) }}">Voltar</a>
    <a class="btn" href="{{ route('member.org.contacts.edit', [$organization, $contact]) }}"><x-icon name="pencil" :size="16"/> Editar lead</a>
  </div>
</div>

<div class="lead-detail-grid">
  <section class="panel-card">
    <div class="panel-head">
      <div class="panel-title"><x-icon name="user-plus" :size="18"/><h2>Dados do lead</h2></div>
    </div>
    <dl class="detail-list">
      <div><dt>ID do lead</dt><dd><span class="lead-id">{{ $contact->lead_id }}</span></dd></div>
      <div><dt>Nome</dt><dd>{{ $contact->name }}</dd></div>
      <div><dt>E-mail</dt><dd>{{ $contact->email ?: '—' }}</dd></div>
      <div><dt>Telefone</dt><dd>{{ $contact->phone ?: '—' }}</dd></div>
      <div><dt>WhatsApp</dt><dd>{{ $contact->whatsapp ?: '—' }}</dd></div>
      <div><dt>Origem</dt><dd>{{ $contact->source ?: '—' }}</dd></div>
      @if ($contact->company)<div><dt>Empresa</dt><dd>{{ $contact->company->name }}</dd></div>@endif
      <div><dt>Responsável</dt><dd>{{ $contact->owner?->name ?? '—' }}</dd></div>
      <div><dt>Atualizado em</dt><dd>{{ $contact->updated_at?->format('d/m/Y H:i') }}</dd></div>
    </dl>
    @if ($contact->notes)
      <div class="detail-notes">
        <span class="muted">Observações</span>
        <p>{!! nl2br(e($contact->notes)) !!}</p>
      </div>
    @endif
  </section>

  <section class="panel-card">
    <div class="panel-head">
      <div class="panel-title"><x-icon name="list-check" :size="18"/><h2>Campos do CRM</h2></div>
      @if (\App\Support\OrgAccess::canData(auth()->user(), $organization, 'org.crm_fields'))
        <a class="panel-link" href="{{ route('member.org.fields.index', $organization) }}">Gerenciar <x-icon name="arrow-right" :size="14"/></a>
      @endif
    </div>

    <dl class="detail-list">
      @forelse ($fields as $field)
        @php $value = $contact->custom[$field->key] ?? null; @endphp
        @if ($field->is_active || filled($value))
          <div>
            <dt>
              {{ $field->label }}
              @unless ($field->is_active)<span class="badge warn">desativado</span>@endunless
            </dt>
            <dd>
              @if ($field->type === 'boolean')
                {{ $value ? 'Sim' : 'Não' }}
              @elseif ($field->type === 'url' && filled($value))
                <a href="{{ $value }}" target="_blank" rel="noopener noreferrer">{{ $value }}</a>
              @else
                {{ filled($value) ? $value : '—' }}
              @endif
            </dd>
          </div>
        @endif
      @empty
        <div><dd class="muted">Nenhum campo configurado.</dd></div>
      @endforelse
    </dl>
  </section>
</div>

<section class="panel-card">
  <div class="panel-head">
    <div class="panel-title"><x-icon name="clock" :size="18"/><h2>Histórico do lead</h2></div>
    <span class="badge">{{ $history->count() }} registro(s)</span>
  </div>

  @if ($history->isEmpty())
    <div class="empty-state">Nenhuma movimentação registrada ainda.</div>
  @else
    <ul class="timeline">
      @foreach ($history as $entry)
        @php
          $isCreated = str_contains($entry->action, 'created');
          $isUpdated = str_contains($entry->action, 'updated');
          $label = $isCreated ? 'Lead criado' : ($isUpdated ? 'Lead atualizado' : 'Movimentação registrada');
          $tone = $isCreated ? 'blue' : ($isUpdated ? 'purple' : 'gray');
        @endphp
        <li class="timeline-item">
          <span class="activity-icon {{ $tone }}"><x-icon :name="$isCreated ? 'user-plus' : 'activity'" :size="16"/></span>
          <div class="timeline-body">
            <strong>{{ $label }}</strong>
            <small>{{ $isCreated ? 'Cadastro do lead' : ($isUpdated ? 'Alteração de dados' : 'Movimentação') }} · {{ $actors[$entry->actor_user_id] ?? 'Sistema' }}</small>
          </div>
          <time>{{ $entry->created_at?->format('d/m/Y H:i') }}</time>
        </li>
      @endforeach
    </ul>
  @endif
</section>
@endsection
