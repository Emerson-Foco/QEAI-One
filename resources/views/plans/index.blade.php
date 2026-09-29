@extends('layouts.panel')

@section('title', 'Planos — QEAI One')

@section('panel')
<div class="page-head">
  <div>
    <span class="eyebrow">Plataforma</span>
    <h1>Planos</h1>
    <p class="muted">Defina os planos e o que cada um inclui. As organizações recebem um plano.</p>
  </div>
</div>

<div class="card">
  <h2>Novo plano</h2>
  <form method="post" action="{{ route('panel.plans.store') }}">
    @csrf
    <div class="grid-2">
      <label>Nome<input name="name" required maxlength="80"></label>
      <label>Preço (R$/mês)<input type="number" name="price_reais" step="0.01" min="0" value="0" required></label>
      <label>Intervalo<select name="interval"><option value="month">Mensal</option><option value="year">Anual</option></select></label>
      <label>Ordem<input type="number" name="sort" min="0" value="0"></label>
    </div>
    <label>Descrição<input name="description" maxlength="255"></label>
    <label class="check"><input type="checkbox" name="is_public" value="1"> Exibir publicamente</label>

    <h2 style="margin-top:22px">Recursos e limites</h2>
    <div class="perm-grid">
      @foreach ($catalog as $key => $meta)
        @if ($meta['type'] === 'toggle')
          <label class="check"><input type="checkbox" name="enabled[]" value="{{ $key }}"> {{ $meta['label'] }}</label>
        @else
          <label>{{ $meta['label'] }}<input type="number" name="limits[{{ $key }}]" min="0" placeholder="vazio = ilimitado"></label>
        @endif
      @endforeach
    </div>
    <button class="btn">Criar plano</button>
  </form>
</div>

@foreach ($plans as $plan)
  <div class="card">
    <form method="post" action="{{ route('panel.plans.update', $plan) }}">
      @csrf @method('PUT')
      <div class="page-head" style="margin-bottom:14px">
        <div>
          <h2 style="margin:0">{{ $plan->name }} — {{ $plan->priceFormatted() }}/{{ $plan->interval === 'year' ? 'ano' : 'mês' }}</h2>
          <small class="muted">{{ $plan->slug }} · {{ $plan->organizations_count }} organização(ões) usam este plano</small>
        </div>
      </div>
      <div class="grid-2">
        <label>Nome<input name="name" value="{{ $plan->name }}" required maxlength="80"></label>
        <label>Preço (R$)<input type="number" name="price_reais" step="0.01" min="0" value="{{ number_format($plan->price_cents / 100, 2, '.', '') }}" required></label>
        <label>Intervalo<select name="interval"><option value="month" @selected($plan->interval === 'month')>Mensal</option><option value="year" @selected($plan->interval === 'year')>Anual</option></select></label>
        <label>Ordem<input type="number" name="sort" min="0" value="{{ $plan->sort }}"></label>
      </div>
      <label>Descrição<input name="description" value="{{ $plan->description }}" maxlength="255"></label>
      <label class="check"><input type="checkbox" name="is_public" value="1" @checked($plan->is_public)> Exibir publicamente</label>

      <div class="perm-grid">
        @foreach ($catalog as $key => $meta)
          @php $feature = $plan->feature($key); @endphp
          @if ($meta['type'] === 'toggle')
            <label class="check"><input type="checkbox" name="enabled[]" value="{{ $key }}" @checked($feature?->enabled)> {{ $meta['label'] }}</label>
          @else
            <label>{{ $meta['label'] }}<input type="number" name="limits[{{ $key }}]" min="0" value="{{ $feature?->limit_value }}" placeholder="vazio = ilimitado"></label>
          @endif
        @endforeach
      </div>
      <div class="btn-row">
        <button class="btn secondary">Salvar plano</button>
      </div>
    </form>
    @unless ($plan->organizations_count > 0)
      <form method="post" action="{{ route('panel.plans.destroy', $plan) }}" onsubmit="return confirm('Excluir este plano?');" style="margin-top:10px">
        @csrf @method('DELETE')
        <button class="btn secondary">Excluir plano</button>
      </form>
    @endunless
  </div>
@endforeach
@endsection
