@extends('layouts.member')

@section('title', 'Anúncios — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <span class="pill">Marketing</span>
    <h1>Anúncios</h1>
    <p class="muted">Contas de anúncio, importação de métricas por webhook e relatório.</p>
  </div>
</div>

@if (session('new_ad_url'))
  <div class="alert ok">Conta criada. URL de importação de métricas: <code style="word-break:break-all">{{ session('new_ad_url') }}</code><br><small>POST JSON: <code>{"date":"2026-09-28","impressions":1000,"clicks":40,"spend":150.00,"conversions":5}</code></small></div>
@endif

<div class="card">
  <h2>Adicionar conta de anúncio</h2>
  <form method="post" action="{{ route('member.org.ads.accounts.store', $organization) }}" class="grid-2">
    @csrf
    <label>Nome<input name="name" required maxlength="120" placeholder="Meta Ads da marca"></label>
    <label>Provedor<select name="provider">@foreach ($providers as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
    <label>ID externo da conta<input name="external_account_id" maxlength="120"></label>
    <label>Access token (opcional)<input name="access_token" maxlength="2000"></label>
    <div class="full"><button class="btn">Adicionar conta</button></div>
  </form>
  <table class="table">
    <thead><tr><th>Nome</th><th>Provedor</th><th>Status</th><th>Importação</th><th></th></tr></thead>
    <tbody>
      @forelse ($accounts as $account)
        <tr>
          <td>{{ $account->name }}</td>
          <td>{{ $providers[$account->provider] ?? $account->provider }}</td>
          <td>{{ $account->is_active ? 'Ativa' : 'Inativa' }}</td>
          <td><small class="muted">{{ url('/hooks/ads/' . $account->token) }}</small></td>
          <td class="right"><div class="row-actions inline">
            <form method="post" action="{{ route('member.org.ads.accounts.toggle', [$organization, $account]) }}">@csrf<button class="btn secondary small">{{ $account->is_active ? 'Desativar' : 'Ativar' }}</button></form>
            <form method="post" action="{{ route('member.org.ads.accounts.destroy', [$organization, $account]) }}" onsubmit="return confirm('Remover conta?');">@csrf @method('DELETE')<button class="btn secondary small">Excluir</button></form>
          </div></td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted">Nenhuma conta de anúncio.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Lançar métricas</h2>
  <form method="post" action="{{ route('member.org.ads.metrics.store', $organization) }}" class="grid-2">
    @csrf
    <label>Conta<select name="ad_account_id" required>@foreach ($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></label>
    <label>Data<input type="date" name="date" required></label>
    <label>Impressões<input type="number" name="impressions" min="0" value="0"></label>
    <label>Cliques<input type="number" name="clicks" min="0" value="0"></label>
    <label>Investimento (R$)<input type="number" name="spend_reais" step="0.01" min="0" value="0"></label>
    <label>Conversões<input type="number" name="conversions" min="0" value="0"></label>
    <div class="full"><button class="btn">Salvar métricas</button></div>
  </form>
</div>

<div class="card">
  <h2>Relatório</h2>
  <form method="get" class="inline-form">
    <label style="flex-direction:row;align-items:center;gap:8px;margin:0">De <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
    <label style="flex-direction:row;align-items:center;gap:8px;margin:0">Até <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
    <button class="btn secondary">Filtrar</button>
  </form>

  <ul class="feature-list">
    <li>Impressões: <strong>{{ number_format($summary['impressions'], 0, ',', '.') }}</strong></li>
    <li>Cliques: <strong>{{ number_format($summary['clicks'], 0, ',', '.') }}</strong></li>
    <li>Investimento: <strong>R$ {{ number_format($summary['spend_cents'] / 100, 2, ',', '.') }}</strong></li>
    <li>Conversões: <strong>{{ number_format($summary['conversions'], 0, ',', '.') }}</strong></li>
    <li>CTR: <strong>{{ $summary['impressions'] > 0 ? number_format($summary['clicks'] / $summary['impressions'] * 100, 2, ',', '.') : '0,00' }}%</strong></li>
    <li>CPC: <strong>R$ {{ $summary['clicks'] > 0 ? number_format($summary['spend_cents'] / 100 / $summary['clicks'], 2, ',', '.') : '0,00' }}</strong></li>
    <li>CPL/CPA: <strong>R$ {{ $summary['conversions'] > 0 ? number_format($summary['spend_cents'] / 100 / $summary['conversions'], 2, ',', '.') : '0,00' }}</strong></li>
  </ul>

  <table class="table">
    <thead><tr><th>Data</th><th>Conta</th><th>Impressões</th><th>Cliques</th><th>Investimento</th><th>Conversões</th><th></th></tr></thead>
    <tbody>
      @forelse ($metrics as $metric)
        <tr>
          <td>{{ \Illuminate\Support\Carbon::parse($metric->date)->format('d/m/Y') }}</td>
          <td>{{ $metric->account?->name ?? '—' }}</td>
          <td>{{ number_format($metric->impressions, 0, ',', '.') }}</td>
          <td>{{ number_format($metric->clicks, 0, ',', '.') }}</td>
          <td>R$ {{ number_format($metric->spend_cents / 100, 2, ',', '.') }}</td>
          <td>{{ number_format($metric->conversions, 0, ',', '.') }}</td>
          <td class="right">
            <form method="post" action="{{ route('member.org.ads.metrics.destroy', [$organization, $metric]) }}" onsubmit="return confirm('Remover registro?');">@csrf @method('DELETE')<button class="btn secondary small">Excluir</button></form>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="muted">Nenhuma métrica registrada.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
