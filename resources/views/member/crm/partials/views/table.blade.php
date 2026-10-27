@if ($contacts->isEmpty())
  <div class="card">
    <div class="empty-state">
      <x-icon name="users" :size="30"/>
      <h2 style="margin-top:8px">{{ $search !== '' || $hasFilters ? 'Nenhum lead encontrado' : 'Nenhum lead cadastrado ainda' }}</h2>
      <p class="muted">{{ $search !== '' || $hasFilters ? 'Ajuste a busca ou os filtros para ver mais resultados.' : 'Cadastre o primeiro lead para começar a acompanhar sua operação.' }}</p>
      @if ($search === '' && ! $hasFilters)
        <a class="btn" href="{{ route('member.org.contacts.create', $organization) }}"><x-icon name="plus" :size="16"/> Cadastrar primeiro lead</a>
      @endif
    </div>
  </div>
@else
  <div class="card">
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>ID do lead</th>
            <th>Lead</th>
            @foreach ($listFields as $field)
              @continue($field->key === 'nome')
              <th>{{ $field->label }}</th>
            @endforeach
            <th>Criado em</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($contacts as $contact)
            <tr>
              <td><a class="lead-id nowrap" href="{{ route('member.org.contacts.show', [$organization, $contact]) }}" title="{{ $contact->lead_id }}">{{ $contact->lead_id }}</a></td>
              <td>
                <a class="lead-name" href="{{ route('member.org.contacts.show', [$organization, $contact]) }}">{{ $contact->displayName() }}</a>
              </td>
              @foreach ($listFields as $field)
                @continue($field->key === 'nome')
                @php
                  $value = $contact->custom[$field->key] ?? null;
                  $display = \App\Support\FieldValue::display($field, $value);
                  $optionColors = [];
                  if ($field->type === 'select') {
                      $palette = ['#3B82F6', '#7C3AED', '#EC4899', '#F59E0B', '#10B981', '#0EA5E9'];
                      foreach (array_values($field->options ?? []) as $index => $option) {
                          $optionColors[(string) $option] = $palette[$index % count($palette)];
                      }
                  }
                @endphp
                <td class="nowrap">
                  @if ($field->type === 'select' && filled($value))
                    <span class="option-pill" style="--pill: {{ $optionColors[(string) $value] ?? '#94A3B8' }}">{{ $value }}</span>
                  @else
                    {{ $display }}
                  @endif
                </td>
              @endforeach
              <td class="nowrap">{{ $contact->created_at?->format('d/m/Y') }}</td>
              <td class="right">
                <div class="row-actions inline">
                  <a class="btn secondary small" href="{{ route('member.org.contacts.show', [$organization, $contact]) }}">Ver</a>
                  <a class="btn secondary small" href="{{ route('member.org.contacts.edit', [$organization, $contact]) }}">Editar</a>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{ $contacts->links() }}
  </div>
@endif
