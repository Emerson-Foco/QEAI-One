@if ($dateField === null)
  <div class="card">
    <div class="empty-state">
      <x-icon name="calendar" :size="30"/>
      <h2 style="margin-top:8px">Crie um campo de data para usar o calendário</h2>
      <p class="muted">Adicione um campo do tipo “Data” na Gestão de CRM — por exemplo, “Data de contato” ou “Retorno agendado”.</p>
      @if (\App\Support\OrgAccess::canData(auth()->user(), $organization, 'org.crm_fields'))
        <a class="btn" href="{{ route('member.org.fields.index', $organization) }}"><x-icon name="settings" :size="16"/> Abrir Gestão de CRM</a>
      @endif
    </div>
  </div>
@else
  @php
    $monthNames = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
    $weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
  @endphp

  <div class="card calendar-card">
    <div class="calendar-head">
      <div>
        <h2>{{ $monthNames[$month->month] }} {{ $month->year }}</h2>
        <p class="muted">Campo: <strong>{{ $dateField->label }}</strong>@if ($calendarUndated > 0) · {{ $calendarUndated }} lead(s) sem esse campo @endif</p>
      </div>
      <span class="badge">{{ $filteredCount }} lead(s) no filtro</span>
    </div>

    <div class="calendar-scroll">
      <div class="calendar-grid">
        @foreach ($weekDays as $weekDay)
          <div class="calendar-weekday">{{ $weekDay }}</div>
        @endforeach

        @foreach ($calendarDays as $day)
          <div class="calendar-day {{ $day['in_month'] ? '' : 'is-outside' }} {{ $day['is_today'] ? 'is-today' : '' }}">
            <span class="calendar-day-number">{{ $day['date']->day }}</span>
            <div class="calendar-events">
              @foreach (array_slice($day['leads'], 0, 3) as $lead)
                <a class="calendar-event" href="{{ route('member.org.contacts.show', [$organization, $lead]) }}" title="{{ $lead->name }}">
                  <span class="calendar-dot"></span>
                  <span>{{ \Illuminate\Support\Str::limit($lead->name, 18) }}</span>
                </a>
              @endforeach
              @if (count($day['leads']) > 3)
                <span class="calendar-more">+{{ count($day['leads']) - 3 }} lead(s)</span>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
@endif
