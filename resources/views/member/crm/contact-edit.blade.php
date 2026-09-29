@extends('layouts.member')

@section('title', 'Editar contato — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <a class="muted" href="{{ route('member.org.contacts.index', $organization) }}">← Contatos</a>
    <h1>Editar contato</h1>
  </div>
</div>

<div class="card">
  <form method="post" action="{{ route('member.org.contacts.update', [$organization, $contact]) }}">
    @csrf @method('PUT')
    <div class="grid-2">
      <label>Nome<input name="name" value="{{ old('name', $contact->name) }}" required maxlength="180"></label>
      <label>E-mail<input type="email" name="email" value="{{ old('email', $contact->email) }}" maxlength="180"></label>
      <label>Telefone<input name="phone" value="{{ old('phone', $contact->phone) }}" maxlength="40"></label>
      <label>WhatsApp<input name="whatsapp" value="{{ old('whatsapp', $contact->whatsapp) }}" maxlength="40"></label>
      <label>Cargo<input name="job_title" value="{{ old('job_title', $contact->job_title) }}" maxlength="120"></label>
      <label>Origem<input name="source" value="{{ old('source', $contact->source) }}" maxlength="60"></label>
      <label>Empresa<select name="company_id"><option value="">—</option>@foreach ($companies as $company)<option value="{{ $company->id }}" @selected($contact->company_id === $company->id)>{{ $company->name }}</option>@endforeach</select></label>
      <label>Tags (separadas por vírgula)<input name="tags" value="{{ old('tags', $contact->tags->pluck('name')->implode(', ')) }}" maxlength="300"></label>
      <label class="full">Observações<textarea name="notes" rows="4" maxlength="5000">{{ old('notes', $contact->notes) }}</textarea></label>
    </div>
    <div class="btn-row">
      <button class="btn">Salvar contato</button>
      <a class="btn secondary" href="{{ route('member.org.contacts.index', $organization) }}">Cancelar</a>
    </div>
  </form>
</div>
@endsection
