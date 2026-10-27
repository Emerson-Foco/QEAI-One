@extends('layouts.member')

@section('title', 'Leads — ' . $organization->name)

@php
  $queryBase = request()->query();
  unset($queryBase['page']);
  $viewUrl = fn ($target) => route('member.org.contacts.index', array_merge($queryBase, ['organization' => $organization, 'view' => $target]));
  $monthPrev = $month->copy()->subMonthNoOverflow()->format('Y-m');
  $monthNext = $month->copy()->addMonthNoOverflow()->format('Y-m');
  $monthQuery = fn (string $targetMonth) => route('member.org.contacts.index', array_merge($queryBase, ['organization' => $organization, 'view' => $view, 'month' => $targetMonth]));
  $hasFilters = collect($filters)->contains(function ($filter) {
      if (is_array($filter)) {
          return filled($filter['value'] ?? null) || filled($filter['from'] ?? null) || filled($filter['to'] ?? null);
      }
      return filled($filter);
  });
  $activeFilterCount = collect($filters)->filter(function ($filter) {
      if (is_array($filter)) {
          return filled($filter['value'] ?? null) || filled($filter['from'] ?? null) || filled($filter['to'] ?? null);
      }
      return filled($filter);
  })->count();
  $clearQuery = collect($queryBase)->except(['filters', 'q', 'page'])->all();
  $clearUrl = route('member.org.contacts.index', array_merge($clearQuery, ['organization' => $organization, 'view' => $view]));
  $tabs = [
      'table' => ['label' => 'Tabela', 'icon' => 'list-check'],
      'board' => ['label' => 'Kanban', 'icon' => 'grid'],
      'calendar' => ['label' => 'Calendário', 'icon' => 'calendar'],
      'timeline' => ['label' => 'Cronograma', 'icon' => 'bar-chart'],
  ];
@endphp

@section('panel')
<div class="page-header">
  <div>
    <span class="page-kicker">CRM</span>
    <h1>Leads</h1>
    <p class="muted">Visualize a mesma base de leads em tabela, kanban, calendário ou cronograma.</p>
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
  <div><span class="muted">Resultado dos filtros</span><strong>{{ number_format($filteredCount, 0, ',', '.') }}</strong></div>
</div>

<div class="card view-controls">
  <div class="view-toolbar">
    <nav class="view-tabs" aria-label="Modos de visualização">
      @foreach ($tabs as $key => $tab)
        <a class="view-tab {{ $view === $key ? 'active' : '' }}" href="{{ $viewUrl($key) }}">
          <x-icon :name="$tab['icon']" :size="15"/> {{ $tab['label'] }}
        </a>
      @endforeach
    </nav>

    @if ($view === 'calendar' || $view === 'timeline')
      <div class="month-nav">
        <a class="icon-btn" href="{{ $monthQuery($monthPrev) }}" aria-label="Mês anterior">‹</a>
        <strong>
          @if ($view === 'calendar')
            {{ $month->copy()->startOfMonth()->format('m/Y') }}
          @else
            {{ $timeline['start']->format('m/Y') }} – {{ $timeline['end']->format('m/Y') }}
          @endif
        </strong>
        <a class="icon-btn" href="{{ $monthQuery($monthNext) }}" aria-label="Próximo mês">›</a>
        <a class="btn ghost small" href="{{ $monthQuery(now()->format('Y-m')) }}">Hoje</a>
      </div>
    @endif
  </div>

  <form method="get" action="{{ route('member.org.contacts.index', $organization) }}">
    <input type="hidden" name="view" value="{{ $view }}">

    <div class="view-filter-row">
      <span class="searchbar lead-search">
        <x-icon name="search" :size="18"/>
        <input type="search" name="q" value="{{ $search }}" placeholder="Buscar por ID do lead, nome, e-mail ou telefone" aria-label="Buscar leads">
      </span>

      @if ($view === 'board' && $selectFields->isNotEmpty())
        <label class="inline-field">Agrupar por
          <select name="group" onchange="this.form.submit()">
            @foreach ($selectFields as $field)
              <option value="{{ $field->key }}" @selected($groupField?->key === $field->key)>{{ $field->label }}</option>
            @endforeach
          </select>
        </label>
      @endif

      @if ($view === 'calendar' && $dateFields->isNotEmpty())
        <label class="inline-field">Campo de data
          <select name="date_field" onchange="this.form.submit()">
            @foreach ($dateFields as $field)
              <option value="{{ $field->key }}" @selected($dateField?->key === $field->key)>{{ $field->label }}</option>
            @endforeach
          </select>
        </label>
      @endif

      @if ($view === 'timeline' && $dateFields->isNotEmpty())
        <label class="inline-field">Início
          <select name="start_field" onchange="this.form.submit()">
            @foreach ($dateFields as $field)
              <option value="{{ $field->key }}" @selected($startField?->key === $field->key)>{{ $field->label }}</option>
            @endforeach
          </select>
        </label>
        <label class="inline-field">Fim
          <select name="end_field" onchange="this.form.submit()">
            <option value="">Mesma data</option>
            @foreach ($dateFields as $field)
              <option value="{{ $field->key }}" @selected($endField?->key === $field->key)>{{ $field->label }}</option>
            @endforeach
          </select>
        </label>
      @endif

      <button class="btn secondary"><x-icon name="filter" :size="15"/> Aplicar</button>
    </div>

    <details class="filters-panel" @if ($hasFilters) open @endif>
      <summary><x-icon name="filter" :size="15"/> Filtros por campo @if ($hasFilters)<span class="badge">{{ $activeFilterCount }}</span>@endif</summary>
      <div class="filter-grid">
        @foreach ($fields as $field)
          @php $filter = $filters[$field->key] ?? []; @endphp
          @if ($field->type === 'date')
            <div class="filter-field">
              <span>{{ $field->label }}</span>
              <div class="filter-range">
                <input type="date" name="filters[{{ $field->key }}][from]" value="{{ is_array($filter) ? ($filter['from'] ?? '') : '' }}" aria-label="{{ $field->label }} de">
                <span>até</span>
                <input type="date" name="filters[{{ $field->key }}][to]" value="{{ is_array($filter) ? ($filter['to'] ?? '') : '' }}" aria-label="{{ $field->label }} até">
              </div>
            </div>
          @elseif ($field->type === 'select')
            <label class="filter-field">{{ $field->label }}
              <select name="filters[{{ $field->key }}]">
                <option value="">Todos</option>
                @foreach ($field->options ?? [] as $option)
                  <option value="{{ $option }}" @selected((string) (is_array($filter) ? ($filter['value'] ?? '') : $filter) === (string) $option)>{{ $option }}</option>
                @endforeach
              </select>
            </label>
          @elseif ($field->type === 'boolean')
            <label class="filter-field">{{ $field->label }}
              <select name="filters[{{ $field->key }}]">
                <option value="">Todos</option>
                <option value="1" @selected((string) (is_array($filter) ? ($filter['value'] ?? '') : $filter) === '1')>Sim</option>
                <option value="0" @selected((string) (is_array($filter) ? ($filter['value'] ?? '') : $filter) === '0')>Não</option>
              </select>
            </label>
          @elseif ($field->type === 'number')
            <label class="filter-field">{{ $field->label }}
              <input type="number" step="any" name="filters[{{ $field->key }}]" value="{{ is_array($filter) ? ($filter['value'] ?? '') : $filter }}" placeholder="Valor exato">
            </label>
          @else
            <label class="filter-field">{{ $field->label }}
              <input name="filters[{{ $field->key }}]" value="{{ is_array($filter) ? ($filter['value'] ?? '') : $filter }}" placeholder="Contém…">
            </label>
          @endif
        @endforeach
      </div>
      <div class="filter-actions">
        <button class="btn small">Aplicar filtros</button>
        <a class="btn ghost small" href="{{ $clearUrl }}">Limpar filtros</a>
      </div>
    </details>

    <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
  </form>
</div>

@if ($view === 'board')
  @include('member.crm.partials.views.board')
@elseif ($view === 'calendar')
  @include('member.crm.partials.views.calendar')
@elseif ($view === 'timeline')
  @include('member.crm.partials.views.timeline')
@else
  @include('member.crm.partials.views.table')
@endif
@endsection
