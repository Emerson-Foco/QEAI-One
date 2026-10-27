@extends('layouts.member')

@section('title', 'Dashboard — ' . $organization->name)

@section('panel')
@php
  $firstName = collect(explode(' ', (string) auth()->user()->name))->filter()->first() ?: 'você';
  $canFields = \App\Support\OrgAccess::canData(auth()->user(), $organization, 'org.crm_fields');
@endphp

<div class="page-header">
  <div>
    <span class="page-kicker">Visão geral</span>
    <h1>Olá, {{ $firstName }}!</h1>
    <p class="muted">Aqui está o resumo dos seus leads e da configuração do CRM.</p>
  </div>
  <div class="page-actions">
    <form class="period-chip" method="get" action="{{ route('member.org.dashboard', $organization) }}">
      <x-icon name="calendar" :size="16"/>
      <input type="date" name="from" value="{{ $from->toDateString() }}" aria-label="Data inicial">
      <span class="muted" style="font-size:.72rem">até</span>
      <input type="date" name="to" value="{{ $to->toDateString() }}" aria-label="Data final">
      <button class="btn secondary small" type="submit" style="margin-left:4px">Aplicar</button>
    </form>
  </div>
</div>

<div class="quick-actions" style="margin-bottom:18px">
  <a class="quick-action" href="{{ route('member.org.contacts.create', $organization) }}">
    <x-icon name="user-plus" :size="15"/> Novo lead
  </a>
  @if ($canFields)
    <a class="quick-action" href="{{ route('member.org.fields.index', $organization) }}">
      <x-icon name="settings" :size="15"/> Gestão de CRM
    </a>
  @endif
  <a class="quick-action" href="{{ route('member.org.contacts.index', $organization) }}">
    <x-icon name="users" :size="15"/> Ver todos os leads
  </a>
</div>

<div class="kpi-grid">
  @foreach ($kpis as $kpi)
    <div class="kpi-card">
      <div class="kpi-top">
        <span class="kpi-icon {{ $kpi['tone'] }}"><x-icon :name="$kpi['icon']" :size="21"/></span>
        @if ($kpi['delta']['percent'] !== null)
          <span class="kpi-delta {{ $kpi['delta']['direction'] === 'down' ? 'down' : ($kpi['delta']['direction'] === 'flat' ? 'flat' : '') }}">
            {{ $kpi['delta']['direction'] === 'down' ? '↓' : ($kpi['delta']['direction'] === 'up' ? '↑' : '→') }}
            {{ abs($kpi['delta']['percent']) }}%
          </span>
        @elseif ($kpi['delta']['direction'] === 'up')
          <span class="kpi-delta">↑ Novo</span>
        @else
          <span class="kpi-delta flat">—</span>
        @endif
      </div>
      <div class="kpi-label">{{ $kpi['label'] }}</div>
      <div class="kpi-value" title="{{ $kpi['value'] }}">{{ $kpi['value'] }}</div>
      <div class="kpi-foot">{{ $kpi['detail'] }}</div>
    </div>
  @endforeach
</div>

@if ($totalLeads === 0)
  <section class="panel-card" style="margin-bottom:18px">
    <div class="panel-head">
      <div class="panel-title"><x-icon name="sparkles" :size="18"/><h2>Comece por aqui</h2></div>
    </div>
    <div class="start-grid">
      @if ($canFields)
        <a class="start-step" href="{{ route('member.org.fields.index', $organization) }}">
          <span class="start-number">1</span>
          <strong>Configure os campos do CRM</strong>
          <small>Crie os campos que a sua empresa usa para qualificar um lead.</small>
        </a>
      @endif
      <a class="start-step" href="{{ route('member.org.contacts.create', $organization) }}">
        <span class="start-number">2</span>
        <strong>Cadastre o primeiro lead</strong>
        <small>Preencha os dados e acompanhe tudo pelo ID permanente do lead.</small>
      </a>
      <a class="start-step" href="{{ route('member.org.contacts.index', $organization) }}">
        <span class="start-number">3</span>
        <strong>Acompanhe a lista de leads</strong>
        <small>Busque, edite e consulte o histórico de cada lead da organização.</small>
      </a>
    </div>
  </section>
@endif

<div class="dashboard-grid">
  <section class="panel-card">
    <div class="panel-head">
      <div class="panel-title">
        <x-icon name="chart-pie" :size="18"/>
        <h2>Leads por origem</h2>
      </div>
      <span class="badge">{{ $from->format('d/m') }} – {{ $to->format('d/m') }}</span>
    </div>

    @if ($sourceTotal > 0)
      <div class="donut-wrap">
        <div class="donut" style="background: conic-gradient({{ $donutGradient }})">
          <div class="donut-center">
            <strong>{{ number_format($sourceTotal, 0, ',', '.') }}</strong>
            <small>leads no período</small>
          </div>
        </div>
        <div class="legend">
          @foreach ($sourceRows as $row)
            <div class="legend-row">
              <span class="legend-dot" style="background: {{ $row['color'] }}"></span>
              <span class="legend-name">{{ $row['name'] }}</span>
              <span class="legend-value">{{ number_format($row['percent'], 0, ',', '.') }}%</span>
            </div>
          @endforeach
        </div>
      </div>
    @else
      <div class="empty-state">
        <x-icon name="chart-pie" :size="26"/>
        <p style="margin:0">Nenhum lead captado no período.<br><a href="{{ route('member.org.contacts.create', $organization) }}">Cadastrar um lead</a></p>
      </div>
    @endif
  </section>

  <section class="panel-card">
    <div class="panel-head">
      <div class="panel-title"><x-icon name="clock" :size="18"/><h2>Leads recentes</h2></div>
      <a class="panel-link" href="{{ route('member.org.contacts.index', $organization) }}">Ver todos <x-icon name="arrow-right" :size="14"/></a>
    </div>

    @if ($recentLeads->isEmpty())
      <div class="empty-state">
        <x-icon name="users" :size="26"/>
        <p style="margin:0">Os últimos leads cadastrados aparecerão aqui.</p>
      </div>
    @else
      <table class="table">
        <thead><tr><th>Lead</th><th>ID</th><th>Criado</th></tr></thead>
        <tbody>
          @foreach ($recentLeads as $lead)
            <tr>
              <td>
                <a class="lead-name" href="{{ route('member.org.contacts.show', [$organization, $lead]) }}">{{ $lead->displayName() }}</a>
                <small class="muted" style="display:block">{{ $lead->email ?: ($lead->phone ?: '—') }}</small>
              </td>
              <td><a class="lead-id" href="{{ route('member.org.contacts.show', [$organization, $lead]) }}">{{ $lead->lead_id }}</a></td>
              <td class="nowrap">{{ $lead->created_at?->format('d/m') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </section>
</div>
@endsection
