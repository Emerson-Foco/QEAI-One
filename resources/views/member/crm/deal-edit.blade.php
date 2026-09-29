@extends('layouts.member')

@section('title', 'Editar negócio — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <a class="muted" href="{{ route('member.org.pipeline.index', $organization) }}">← Negócios</a>
    <h1>Editar negócio</h1>
  </div>
</div>

<div class="card">
  <form method="post" action="{{ route('member.org.deals.update', [$organization, $deal]) }}">
    @csrf @method('PUT')
    <div class="grid-2">
      <label>Título<input name="title" value="{{ old('title', $deal->title) }}" required maxlength="180"></label>
      <label>Valor (R$)<input type="number" name="value_reais" step="0.01" min="0" value="{{ number_format($deal->value_cents / 100, 2, '.', '') }}"></label>
      <label>Etapa<select name="stage_id">@foreach ($stages as $stage)<option value="{{ $stage->id }}" @selected($deal->stage_id === $stage->id)>{{ $stage->name }}</option>@endforeach</select></label>
      <label>Previsão de fechamento<input type="date" name="expected_close_at" value="{{ old('expected_close_at', $deal->expected_close_at?->format('Y-m-d')) }}"></label>
      <label>Contato<select name="contact_id"><option value="">—</option>@foreach ($contacts as $contact)<option value="{{ $contact->id }}" @selected($deal->contact_id === $contact->id)>{{ $contact->name }}</option>@endforeach</select></label>
      <label>Empresa<select name="company_id"><option value="">—</option>@foreach ($companies as $company)<option value="{{ $company->id }}" @selected($deal->company_id === $company->id)>{{ $company->name }}</option>@endforeach</select></label>
      <label class="full">Observações<textarea name="notes" rows="3" maxlength="5000">{{ old('notes', $deal->notes) }}</textarea></label>
    </div>
    <div class="btn-row">
      <button class="btn">Salvar negócio</button>
      <a class="btn secondary" href="{{ route('member.org.pipeline.index', $organization) }}">Cancelar</a>
    </div>
  </form>
</div>
@endsection
