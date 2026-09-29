@for ($i = 0; $i < 8; $i++)
  @php $row = $rows[$i] ?? []; @endphp
  <div class="field-row">
    <label>Campo<input name="fields[{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" maxlength="120" placeholder="Ex.: Segmento"></label>
    <label>Tipo<select name="fields[{{ $i }}][type]">@foreach ($types as $key => $label)<option value="{{ $key }}" @selected(($row['type'] ?? 'text') === $key)>{{ $label }}</option>@endforeach</select></label>
    <label>Opções (p/ seleção)<input name="fields[{{ $i }}][options]" value="{{ isset($row['options']) ? implode(', ', $row['options']) : '' }}" maxlength="500"></label>
    <div class="field-row-checks">
      <label class="check"><input type="checkbox" name="fields[{{ $i }}][required]" value="1" @checked($row['required'] ?? false)> Obrigatório</label>
      <label class="check"><input type="checkbox" name="fields[{{ $i }}][show_in_list]" value="1" @checked($row['show_in_list'] ?? false)> Na lista</label>
    </div>
  </div>
@endfor
