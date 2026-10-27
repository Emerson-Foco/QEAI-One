@if ($groupField === null)
  <div class="card">
    <div class="empty-state">
      <x-icon name="grid" :size="30"/>
      <h2 style="margin-top:8px">Crie um campo de seleção para usar o Kanban</h2>
      <p class="muted">O Kanban agrupa os leads pelas opções de um campo do tipo “Seleção”, como Status, Temperatura ou Segmento.</p>
      @if (\App\Support\OrgAccess::canData(auth()->user(), $organization, 'org.crm_fields'))
        <a class="btn" href="{{ route('member.org.fields.index', $organization) }}"><x-icon name="settings" :size="16"/> Abrir Gestão de CRM</a>
      @endif
    </div>
  </div>
@else
  @php $dateFieldForCard = $dateFields->first(); @endphp
  <div class="lead-board" data-board>
    @foreach ($boardGroups as $groupName => $groupLeads)
      @php $groupValue = $groupName === 'Sem valor' ? '' : (string) $groupName; @endphp
      <section class="board-column" data-board-column data-value="{{ $groupValue }}">
        <header class="board-column-head">
          <span>{{ $groupName }}</span>
          <span class="badge">{{ $groupLeads->count() }}</span>
        </header>

        <div class="board-column-body">
          @forelse ($groupLeads as $contact)
            <article class="board-card" draggable="true" data-board-card data-lead-id="{{ $contact->id }}">
              <a class="lead-name" href="{{ route('member.org.contacts.show', [$organization, $contact]) }}">{{ $contact->displayName() }}</a>
              <span class="lead-id" title="{{ $contact->lead_id }}">{{ $contact->lead_id }}</span>

              @if ($dateFieldForCard)
                @php $dateValue = $contact->custom[$dateFieldForCard->key] ?? null; @endphp
                @if (filled($dateValue))
                  <span class="board-date"><x-icon name="calendar" :size="12"/> {{ \Illuminate\Support\Carbon::parse($dateValue)->format('d/m/Y') }}</span>
                @endif
              @endif

              <form method="post" action="{{ route('member.org.contacts.field', [$organization, $contact]) }}" class="board-move">
                @csrf
                <input type="hidden" name="field" value="{{ $groupField->key }}">
                <select name="value" aria-label="Mover lead para outra opção" onchange="this.form.submit()">
                  @unless ($groupField->required)
                    <option value="" @selected(blank($contact->custom[$groupField->key] ?? null))>Sem valor</option>
                  @endunless
                  @foreach ($groupField->options ?? [] as $option)
                    <option value="{{ $option }}" @selected((string) ($contact->custom[$groupField->key] ?? '') === (string) $option)>{{ $option }}</option>
                  @endforeach
                </select>
              </form>
            </article>
          @empty
            <p class="board-empty">Nenhum lead neste grupo.</p>
          @endforelse
        </div>
      </section>
    @endforeach
  </div>
  <p class="muted board-hint"><x-icon name="grid" :size="14"/> Arraste os cards entre as colunas ou use o seletor dentro do card.</p>
@endif
