@extends('layouts.member')

@section('title', 'Dashboard — ' . $organization->name)

@section('panel')
@php
  $firstName = collect(explode(' ', (string) auth()->user()->name))->filter()->first() ?: 'você';
@endphp

<div class="page-header">
  <div>
    <span class="page-kicker">Visão geral</span>
    <h1>Olá, {{ $firstName }}!</h1>
    <p class="muted">Aqui está um resumo do seu funil de vendas e da sua operação.</p>
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
  <a class="quick-action" href="{{ route('member.org.contacts.index', $organization) }}#novo-contato">
    <x-icon name="user-plus" :size="15"/> Novo lead
  </a>
  <a class="quick-action" href="{{ route('member.org.pipeline.index', $organization) }}#novo-negocio">
    <x-icon name="briefcase" :size="15"/> Nova oportunidade
  </a>
  <a class="quick-action" href="{{ route('member.org.tasks.index', $organization) }}#nova-tarefa">
    <x-icon name="check-square" :size="15"/> Nova tarefa
  </a>
  <a class="quick-action" href="{{ route('member.org.forms.index', $organization) }}">
    <x-icon name="filter" :size="15"/> Criar formulário
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
      <div class="kpi-value">{{ $kpi['value'] }}</div>
      <div class="kpi-foot">{{ $kpi['detail'] }}</div>
    </div>
  @endforeach
</div>

<div class="dashboard-grid">
  <section class="panel-card">
    <div class="panel-head">
      <div class="panel-title">
        <x-icon name="filter" :size="18"/>
        <h2>Funil de vendas</h2>
      </div>
      <a class="panel-link" href="{{ route('member.org.pipeline.index', $organization) }}">Ver detalhes <x-icon name="arrow-right" :size="14"/></a>
    </div>

    @if (collect($funnel)->sum('count') > 0)
      <div class="funnel">
        @foreach ($funnel as $index => $row)
          <div class="funnel-row">
            <div class="funnel-track">
              <div class="funnel-bar funnel-step-{{ $index + 1 }}" style="width: {{ $row['width'] }}%">
                <strong>{{ number_format($row['count'], 0, ',', '.') }}</strong>
                <small>{{ $row['name'] }}</small>
              </div>
            </div>
            <span class="funnel-pct">{{ $row['percent'] }}%</span>
          </div>
        @endforeach
      </div>
      <div class="funnel-foot">
        <span><strong>{{ number_format($funnelTotal, 0, ',', '.') }}</strong> negócios no funil</span>
        <span><strong>R$ {{ number_format($funnelValue / 100, 2, ',', '.') }}</strong> em valor</span>
      </div>
    @else
      <div class="empty-state">
        <x-icon name="briefcase" :size="26"/>
        <p style="margin:0">Nenhuma oportunidade no funil ainda.<br><a href="{{ route('member.org.pipeline.index', $organization) }}#novo-negocio">Criar a primeira oportunidade</a></p>
      </div>
    @endif
  </section>

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
            <small>leads</small>
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
        <p style="margin:0">Nenhum lead captado no período.<br><a href="{{ route('member.org.contacts.index', $organization) }}#novo-contato">Cadastrar um lead</a></p>
      </div>
    @endif
  </section>
</div>

<div class="dashboard-grid">
  <section class="panel-card">
    <div class="panel-head">
      <div class="panel-title">
        <x-icon name="check-square" :size="18"/>
        <h2>Tarefas de hoje</h2>
      </div>
      <a class="panel-link" href="{{ route('member.org.tasks.index', $organization) }}">Ver todas <x-icon name="arrow-right" :size="14"/></a>
    </div>

    @if ($tasks->isEmpty())
      <div class="empty-state">
        <x-icon name="check-square" :size="26"/>
        <p style="margin:0">Nenhuma tarefa para hoje. Tudo em dia! 🎉</p>
      </div>
    @else
      <ul class="task-list">
        @foreach ($tasks as $task)
          <li class="task-item {{ $task->due_at && $task->due_at->lt(today()) ? 'overdue' : '' }}">
            <form class="task-form" method="post" action="{{ route('member.org.tasks.toggle', [$organization, $task]) }}">
              @csrf
              <button class="task-check" type="submit" aria-label="Concluir tarefa">✓</button>
            </form>
            <div class="task-body">
              <strong>{{ $task->title }}</strong>
              <small>{{ $task->contact?->name ?? $task->deal?->title ?? 'Sem vínculo' }}</small>
            </div>
            <span class="task-time">{{ $task->due_at?->format('H:i') }}</span>
          </li>
        @endforeach
      </ul>
    @endif
  </section>

  <section class="panel-card">
    <div class="panel-head">
      <div class="panel-title">
        <x-icon name="clock" :size="18"/>
        <h2>Atividades recentes</h2>
      </div>
      @if (\App\Support\OrgAccess::can(auth()->user(), $organization, 'org.logs'))
        <a class="panel-link" href="{{ route('member.org.logs', $organization) }}">Ver todas <x-icon name="arrow-right" :size="14"/></a>
      @endif
    </div>

    @if ($activities->isEmpty())
      <div class="empty-state">
        <x-icon name="activity" :size="26"/>
        <p style="margin:0">As atividades da equipe aparecerão aqui.</p>
      </div>
    @else
      <ul class="activity-list">
        @foreach ($activities as $activity)
          <li class="activity-item">
            <span class="activity-icon {{ $activity['tone'] }}"><x-icon :name="$activity['icon']" :size="17"/></span>
            <div class="activity-body">
              <strong>{{ $activity['title'] }}</strong>
              <small>{{ $activity['detail'] }}</small>
            </div>
            <span class="activity-time">{{ $activity['time'] }}</span>
          </li>
        @endforeach
      </ul>
    @endif
  </section>
</div>
@endsection
