@extends('layouts.member')

@section('title', 'Formulários — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <span class="pill">Captação</span>
    <h1>Formulários</h1>
    <p class="muted">Crie formulários para o site e receba leads direto no CRM (com campos personalizados).</p>
  </div>
</div>

<div class="card">
  <h2>Novo formulário</h2>
  <form method="post" action="{{ route('member.org.forms.store', $organization) }}">
    @csrf
    <div class="grid-2">
      <label>Nome<input name="name" required maxlength="120" placeholder="Contato do site"></label>
      <label>Redirecionar após envio (opcional)<input type="url" name="redirect_url" maxlength="255" placeholder="https://..."></label>
      <label class="full">Mensagem de sucesso<input name="success_message" maxlength="255" placeholder="Recebemos seu contato. Obrigado!"></label>
      <label class="full">Texto de consentimento (LGPD; se preenchido, o aceite é obrigatório)<input name="consent_text" maxlength="255" placeholder="Autorizo o uso dos meus dados para contato."></label>
    </div>

    <h2 style="margin-top:16px">Campos</h2>
    <table class="table">
      <thead><tr><th>Campo</th><th>Incluir</th><th>Obrigatório</th></tr></thead>
      <tbody>
        @foreach ($builtin as $key => $label)
          <tr>
            <td>{{ $label }}</td>
            <td><input type="checkbox" name="include[]" value="b:{{ $key }}" @checked($key === 'name')></td>
            <td><input type="checkbox" name="required[]" value="b:{{ $key }}" @checked($key === 'name')></td>
          </tr>
        @endforeach
        @foreach ($customFields as $field)
          <tr>
            <td>{{ $field->label }} <small class="muted">(personalizado)</small></td>
            <td><input type="checkbox" name="include[]" value="c:{{ $field->key }}"></td>
            <td><input type="checkbox" name="required[]" value="c:{{ $field->key }}"></td>
          </tr>
        @endforeach
      </tbody>
    </table>
    <button class="btn">Criar formulário</button>
  </form>
</div>

@foreach ($forms as $form)
  <div class="card">
    <h2 style="display:flex;justify-content:space-between;align-items:center">
      {{ $form->name }}
      @unless ($form->is_active)<span class="badge">inativo</span>@endunless
    </h2>
    <p class="muted">Link público: <code style="word-break:break-all">{{ route('form.show', $form->slug) }}</code></p>
    <p class="muted">Incorpore no site: <code style="word-break:break-all">&lt;iframe src="{{ route('form.show', $form->slug) }}" style="width:100%;height:680px;border:0"&gt;&lt;/iframe&gt;</code></p>
    <div class="btn-row">
      <form method="post" action="{{ route('member.org.forms.toggle', [$organization, $form]) }}">@csrf<button class="btn secondary small">{{ $form->is_active ? 'Desativar' : 'Ativar' }}</button></form>
      <form method="post" action="{{ route('member.org.forms.destroy', [$organization, $form]) }}" onsubmit="return confirm('Remover formulário?');">@csrf @method('DELETE')<button class="btn secondary small">Excluir</button></form>
    </div>
  </div>
@endforeach
@endsection
