@if ($startField === null && $endField === null)
  <div class="card">
    <div class="empty-state">
      <x-icon name="bar-chart" :size="30"/>
      <h2 style="margin-top:8px">Crie campos de data para usar o cronograma</h2>
      <p class="muted">Adicione campos do tipo “Data” na Gestão de CRM. Você poderá escolher um para início e outro para fim.</p>
      @if (\App\Support\OrgAccess::canData(auth()->user(), $organization, 'org.crm_fields'))
        <a class="btn" href="{{ route('member.org.fields.index', $organization) }}"><x-icon name="settings" :size="16"/> Abrir Gestão de CRM</a>
      @endif
    </div>
  </div>
@else
  <div class="card timeline-card">
    <div class="calendar-head">
      <div>
        <h2>Cronograma</h2>
        <p class="muted">
          Início: <strong>{{ $startField?->label ?? 'Criado em' }}</strong>
          · Fim: <strong>{{ $endField?->label ?? 'Mesma data' }}</strong>
          @if ($timeline['undated'] > 0) · {{ $timeline['undated'] }} lead(s) sem data @endif
        </p>
      </div>
      <span class="badge">{{ count($timeline['bars']) }} lead(s) no período</span>
    </div>

    @if (empty($timeline['bars']))
      <div class="empty-state">
        <x-icon name="bar-chart" :size="26"/>
        <p style="margin:0">Nenhum lead com data neste intervalo. Navegue para outro período ou preencha os campos de data.</p>
      </div>
    @else
      @if (! empty($timeline['colors']))
        <div class="timeline-legend">
          @foreach ($timeline['colors'] as $label => $color)
            <span><i style="background:{{ $color }}"></i>{{ $label }}</span>
          @endforeach
        </div>
      @endif

      <div class="timeline-scroll">
        <div class="timeline">
          <div class="timeline-header">
            <div class="timeline-label">Lead</div>
            <div class="timeline-track timeline-months">
              @foreach ($timeline['months'] as $monthBlock)
                <span style="width: {{ $monthBlock['width'] }}%">{{ $monthBlock['label'] }}</span>
              @endforeach
            </div>
          </div>

          @foreach ($timeline['bars'] as $bar)
            <div class="timeline-row">
              <div class="timeline-label">
                <a class="lead-name" href="{{ route('member.org.contacts.show', [$organization, $bar['contact']]) }}">{{ $bar['contact']->name }}</a>
                <small>{{ $bar['start']->format('d/m/Y') }} – {{ $bar['end']->format('d/m/Y') }}</small>
              </div>
              <div class="timeline-track">
                @if ($timeline['today_left'] !== null)
                  <span class="timeline-today" style="left: {{ $timeline['today_left'] }}%" title="Hoje"></span>
                @endif
                <a class="timeline-bar" href="{{ route('member.org.contacts.show', [$organization, $bar['contact']]) }}"
                   style="left: {{ $bar['left'] }}%; width: {{ $bar['width'] }}%; background: {{ $bar['color'] }}"
                   title="{{ $bar['contact']->name }}: {{ $bar['start']->format('d/m/Y') }} a {{ $bar['end']->format('d/m/Y') }}">
                  <span>{{ $bar['contact']->name }}</span>
                </a>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </div>
@endif
