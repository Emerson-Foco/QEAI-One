@extends('layouts.member')

@section('title', 'Editar empresa — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <a class="muted" href="{{ route('member.org.companies.index', $organization) }}">← Empresas</a>
    <h1>Editar empresa</h1>
  </div>
</div>

<div class="card">
  <form method="post" action="{{ route('member.org.companies.update', [$organization, $company]) }}">
    @csrf @method('PUT')
    <div class="grid-2">
      <label>Nome<input name="name" value="{{ old('name', $company->name) }}" required maxlength="180"></label>
      <label>CNPJ / documento<input name="document" value="{{ old('document', $company->document) }}" maxlength="24"></label>
      <label>Site<input name="website" value="{{ old('website', $company->website) }}" maxlength="255"></label>
      <label>Telefone<input name="phone" value="{{ old('phone', $company->phone) }}" maxlength="40"></label>
      <label class="full">Observações<textarea name="notes" rows="4" maxlength="5000">{{ old('notes', $company->notes) }}</textarea></label>
    </div>
    <div class="btn-row">
      <button class="btn">Salvar empresa</button>
      <a class="btn secondary" href="{{ route('member.org.companies.index', $organization) }}">Cancelar</a>
    </div>
  </form>
</div>
@endsection
