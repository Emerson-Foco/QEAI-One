@foreach ($fields as $field)
  @php
    $rawValue = old('custom.' . $field->key, $values[$field->key] ?? '');
    $inputValue = \App\Support\FieldValue::input($field, $rawValue);
  @endphp
  @if ($field->type === 'textarea')
    <label class="full">{{ $field->label }}<textarea name="custom[{{ $field->key }}]" rows="2" maxlength="5000" @if ($field->required) required @endif>{{ $rawValue }}</textarea></label>
  @elseif ($field->type === 'select')
    <label>{{ $field->label }}<select name="custom[{{ $field->key }}]" @if ($field->required) required @endif>
      <option value="">—</option>
      @foreach ($field->options ?? [] as $option)
        <option value="{{ $option }}" @selected((string) $rawValue === (string) $option)>{{ $option }}</option>
      @endforeach
    </select></label>
  @elseif ($field->type === 'boolean')
    <label class="check"><input type="checkbox" name="custom[{{ $field->key }}]" value="1" @checked((bool) $rawValue)> {{ $field->label }}</label>
  @else
    @php
      $inputType = match ($field->type) {
          'number' => 'number',
          'date' => 'date',
          'time' => 'time',
          'datetime' => 'datetime-local',
          'email' => 'email',
          'url' => 'url',
          default => 'text',
      };
    @endphp
    <label>{{ $field->label }}<input type="{{ $inputType }}" name="custom[{{ $field->key }}]" value="{{ $inputValue }}" @if ($field->required) required @endif @if ($field->type === 'number') step="any" @endif></label>
  @endif
@endforeach
