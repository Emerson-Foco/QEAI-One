@extends('layouts.member')

@section('title', 'Campos personalizados — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <span class="pill">CRM</span>
    <h1>Campos personalizados</h1>
    <p class="muted">Adapte o CRM ao seu tipo de negócio. Estes campos aparecem nos contatos, nas listas e nos formulários.</p>
  </div>
</div>

<div class="card">
  <h2>Novo campo</h2>
  <form method="post" action="{{ route('member.org.fields.store', $organization) }}">
    @csrf
    <div class="grid-2">
      <label>Nome do campo<input name="label" required maxlength="120" placeholder="Ex.: Segmento"></label>
      <label>Tipo<select name="type">@foreach ($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
      <label class="full">Opções (apenas para "Seleção")<input name="options_text" maxlength="1000" placeholder="Opção A, Opção B, Opção C"></label>
      <label class="check"><input type="checkbox" name="required" value="1"> Obrigatório</label>
      <label class="check"><input type="checkbox" name="show_in_list" value="1"> Mostrar na lista/quadro</label>
    </div>
    <button class="btn">Criar campo</button>
  </form>
</div>

@foreach ($fields as $field)
  <div class="card">
    <form method="post" action="{{ route('member.org.fields.update', [$organization, $field]) }}">
      @csrf @method('PUT')
      <div class="grid-2">
        <label>Nome<input name="label" value="{{ $field->label }}" required maxlength="120"></label>
        <label>Tipo<input value="{{ $types[$field->type] ?? $field->type }}" disabled></label>
        <label class="full">Opções<input name="options_text" value="{{ implode(', ', $field->options ?? []) }}" maxlength="1000" @disabled($field->type !== 'select')></label>
        <label class="check"><input type="checkbox" name="required" value="1" @checked($field->required)> Obrigatório</label>
        <label class="check"><input type="checkbox" name="show_in_list" value="1" @checked($field->show_in_list)> Mostrar na lista/quadro</label>
      </div>
      <div class="btn-row">
        <button class="btn secondary">Salvar campo</button>
        @if ($field->is_system)<span class="badge">padrão</span>@endif
      </div>
    </form>
    @unless ($field->is_system)
      <form method="post" action="{{ route('member.org.fields.destroy', [$organization, $field]) }}" onsubmit="return confirm('Remover campo?');" style="margin-top:10px">
        @csrf @method('DELETE')
        <button class="btn secondary small">Excluir campo</button>
      </form>
    @endunless
  </div>
@endforeach
@endsection
