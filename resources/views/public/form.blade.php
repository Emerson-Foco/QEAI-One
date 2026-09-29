@extends('layouts.app')

@section('title', $form->name . ' — ' . ($organization->name ?? 'QEAI One'))
@section('robots', 'noindex,nofollow')

@section('content')
<div class="center">
  <div class="card narrow">
    <div class="brand"><img src="{{ asset('img/logo.svg') }}" alt="{{ $organization->name ?? 'QEAI One' }}"></div>
    <h1>{{ $form->name }}</h1>

    @if (session('form_success'))
      <div class="alert ok">{{ session('form_success') }}</div>
    @endif
    @if ($errors->any())
      <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="post" action="{{ route('form.submit', $form->slug) }}">
      @csrf
      <div class="hp" aria-hidden="true"><label>Não preencha este campo<input name="website" tabindex="-1" autocomplete="off"></label></div>

      @php $builtin = \App\Models\Form::builtinFields(); @endphp
      @foreach ($form->fields ?? [] as $field)
        @php
          $source = $field['source'] ?? 'builtin';
          $key = $field['key'] ?? '';
          $required = (bool) ($field['required'] ?? false);
        @endphp
        @if ($source === 'builtin')
          @php $label = $builtin[$key] ?? $key; @endphp
          @if ($key === 'notes')
            <label class="full">{{ $label }}<textarea name="notes" rows="3" maxlength="5000" @if ($required) required @endif></textarea></label>
          @else
            <label>{{ $label }}<input type="{{ $key === 'email' ? 'email' : 'text' }}" name="{{ $key }}" @if ($required) required @endif></label>
          @endif
        @else
          @php $def = $fieldDefs->firstWhere('key', $key); @endphp
          @continue($def === null)
          @if ($def->type === 'textarea')
            <label class="full">{{ $def->label }}<textarea name="custom[{{ $key }}]" rows="3" maxlength="5000" @if ($required) required @endif></textarea></label>
          @elseif ($def->type === 'select')
            <label>{{ $def->label }}<select name="custom[{{ $key }}]" @if ($required) required @endif>
              <option value="">—</option>
              @foreach ($def->options ?? [] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
            </select></label>
          @elseif ($def->type === 'boolean')
            <label class="check"><input type="checkbox" name="custom[{{ $key }}]" value="1"> {{ $def->label }}</label>
          @else
            @php $inputType = match ($def->type) { 'number' => 'number', 'date' => 'date', 'email' => 'email', 'url' => 'url', default => 'text' }; @endphp
            <label>{{ $def->label }}<input type="{{ $inputType }}" name="custom[{{ $key }}]" @if ($required) required @endif></label>
          @endif
        @endif
      @endforeach

      @if ($form->consent_text)
        <label class="check"><input type="checkbox" name="consent" value="1" required> {{ $form->consent_text }}</label>
      @endif

      <button class="btn full">Enviar</button>
    </form>
  </div>
</div>
@endsection
