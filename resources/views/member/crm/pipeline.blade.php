@extends('layouts.member')

@section('title', 'Negócios — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <span class="pill">CRM</span>
    <h1>Negócios</h1>
    <p class="muted">Pipeline: {{ $pipeline->name }}</p>
  </div>
</div>

<div class="card">
  <h2>Novo negócio</h2>
  <form method="post" action="{{ route('member.org.deals.store', $organization) }}">
    @csrf
    <div class="grid-2">
      <label>Título<input name="title" required maxlength="180"></label>
      <label>Valor (R$)<input type="number" name="value_reais" step="0.01" min="0" value="0"></label>
      <label>Etapa<select name="stage_id" required>@foreach ($stages as $stage)<option value="{{ $stage->id }}">{{ $stage->name }}</option>@endforeach</select></label>
      <label>Previsão de fechamento<input type="date" name="expected_close_at"></label>
      <label>Contato<select name="contact_id"><option value="">—</option>@foreach ($contacts as $contact)<option value="{{ $contact->id }}">{{ $contact->name }}</option>@endforeach</select></label>
      <label>Empresa<select name="company_id"><option value="">—</option>@foreach ($companies as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach</select></label>
      <label class="full">Observações<textarea name="notes" rows="2" maxlength="5000"></textarea></label>
    </div>
    <button class="btn">Criar negócio</button>
  </form>
</div>

<div class="kanban">
  @foreach ($stages as $stage)
    @php $stageDeals = $deals[$stage->id] ?? collect(); @endphp
    <div class="kanban-col">
      <h3>{{ $stage->name }} · {{ $stageDeals->count() }}</h3>
      @foreach ($stageDeals as $deal)
        <div class="deal-card">
          <strong>{{ $deal->title }}</strong>
          <div class="deal-value">{{ $deal->valueFormatted() }}</div>
          <div class="deal-meta">
            @if ($deal->contact){{ $deal->contact->name }}@endif
            @if ($deal->contact && $deal->company) · @endif
            @if ($deal->company){{ $deal->company->name }}@endif
            @if (! $deal->contact && ! $deal->company)—@endif
          </div>
          <form method="post" action="{{ route('member.org.deals.move', [$organization, $deal]) }}" class="move-form">
            @csrf
            <select name="stage_id">@foreach ($stages as $s)<option value="{{ $s->id }}" @selected($s->id === $deal->stage_id)>{{ $s->name }}</option>@endforeach</select>
            <button class="btn secondary small">Mover</button>
          </form>
          <div class="row-actions inline" style="margin-top:8px">
            <a class="btn secondary small" href="{{ route('member.org.deals.edit', [$organization, $deal]) }}">Editar</a>
            <form method="post" action="{{ route('member.org.deals.destroy', [$organization, $deal]) }}" onsubmit="return confirm('Remover negócio?');">
              @csrf @method('DELETE')
              <button class="btn secondary small">Excluir</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  @endforeach
</div>
@endsection
