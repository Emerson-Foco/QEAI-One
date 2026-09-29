@extends('layouts.panel')

@section('title', 'Templates de campos — QEAI One')

@section('panel')
<div class="page-head">
  <div>
    <span class="eyebrow">Plataforma</span>
    <h1>Templates de campos</h1>
    <p class="muted">Perfis de campos personalizados (ex.: Imobiliária, Clínica, E-commerce) aplicados às novas organizações ou manualmente.</p>
  </div>
</div>

<div class="card">
  <h2>Novo template</h2>
  <form method="post" action="{{ route('panel.templates.store') }}">
    @csrf
    <div class="grid-2">
      <label>Nome<input name="name" required maxlength="120" placeholder="Ex.: Imobiliária"></label>
      <label>Descrição<input name="description" maxlength="255"></label>
    </div>
    <h2 style="margin-top:16px">Campos</h2>
    @include('templates.partials.rows', ['types' => $types, 'rows' => []])
    <button class="btn">Criar template</button>
  </form>
</div>

@foreach ($templates as $template)
  <div class="card">
    <form method="post" action="{{ route('panel.templates.update', $template) }}">
      @csrf @method('PUT')
      <div class="grid-2">
        <label>Nome<input name="name" value="{{ $template->name }}" required maxlength="120"></label>
        <label>Descrição<input name="description" value="{{ $template->description }}" maxlength="255"></label>
      </div>
      @include('templates.partials.rows', ['types' => $types, 'rows' => $template->fields ?? []])
      <div class="btn-row">
        <button class="btn secondary">Salvar template</button>
        @if ($template->is_default)<span class="badge">padrão</span>@endif
      </div>
    </form>
    <div class="btn-row" style="margin-top:10px">
      @unless ($template->is_default)
        <form method="post" action="{{ route('panel.templates.default', $template) }}">@csrf<button class="btn secondary small">Definir como padrão</button></form>
      @endunless
      <form method="post" action="{{ route('panel.templates.destroy', $template) }}" onsubmit="return confirm('Remover template?');">@csrf @method('DELETE')<button class="btn secondary small">Excluir template</button></form>
    </div>
  </div>
@endforeach
@endsection
