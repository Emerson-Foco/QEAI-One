@extends('layouts.member')

@section('title', 'Gestão de CRM — ' . $organization->name)

@section('panel')
<div class="page-header">
  <div>
    <span class="page-kicker">CRM · Configuração</span>
    <h1>Gestão de CRM</h1>
    <p class="muted">Crie os campos que fazem sentido para o seu negócio. Cada campo tem um ID permanente para automações.</p>
  </div>
  <div class="page-actions">
    <a class="btn secondary" href="{{ route('member.org.contacts.index', $organization) }}"><x-icon name="users" :size="16"/> Ver leads</a>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <div>
      <h2>Como funciona</h2>
      <p class="muted">O CRM se adapta ao seu processo, sem perder o histórico.</p>
    </div>
  </div>
  <ul class="guideline-list">
    <li><span class="guideline-icon">1</span><div><strong>ID do lead é fixo</strong><small>Todo lead recebe um ID permanente (ex.: <span class="lead-id">LD-...</span>) usado no histórico e nas integrações.</small></div></li>
    <li><span class="guideline-icon">2</span><div><strong>Campos não são excluídos</strong><small>Ao desativar um campo, ele sai dos formulários, mas os dados já preenchidos permanecem no histórico dos leads.</small></div></li>
    <li><span class="guideline-icon">3</span><div><strong>ID do campo é estável</strong><small>Cada campo recebe um ID próprio (ex.: <span class="lead-id">FLD-...</span>) que não muda ao renomear ou alterar o tipo.</small></div></li>
  </ul>
</div>

<div class="card">
  <div class="card-head">
    <div>
      <h2>Novo campo</h2>
      <p class="muted">Escolha um nome claro e o tipo de preenchimento.</p>
    </div>
  </div>

  <form method="post" action="{{ route('member.org.fields.store', $organization) }}">
    @csrf
    <div class="grid-2">
      <label>Nome do campo
        <input name="label" value="{{ old('label') }}" required maxlength="120" placeholder="Ex.: Segmento, Orçamento, Interesse">
      </label>
      <label>Tipo do campo
        <select name="type" required>
          @foreach ($types as $value => $label)
            <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </label>
      <label class="full">Opções <small>Somente para o tipo “Seleção”. Separe por vírgula.</small>
        <input name="options_text" value="{{ old('options_text') }}" maxlength="2000" placeholder="Varejo, Serviços, Indústria">
      </label>
      <label class="check"><input type="checkbox" name="required" value="1" @checked(old('required'))> Obrigatório no cadastro</label>
      <label class="check"><input type="checkbox" name="show_in_list" value="1" @checked(old('show_in_list'))> Mostrar na lista de leads</label>
    </div>
    <button class="btn"><x-icon name="plus" :size="16"/> Criar campo</button>
  </form>
</div>

<div class="card">
  <div class="card-head">
    <div>
      <h2>Campos configurados</h2>
      <p class="muted">{{ $fields->where('is_active', true)->count() }} ativo(s) · {{ $fields->where('is_active', false)->count() }} desativado(s)</p>
    </div>
  </div>

  <div class="field-admin-list">
    @foreach ($fields as $field)
      <article class="field-admin {{ $field->is_active ? '' : 'is-inactive' }}">
        <div class="field-admin-head">
          <div style="min-width:0">
            <div class="field-admin-title">
              <strong>{{ $field->label }}</strong>
              @if ($field->is_system)<span class="badge">padrão</span>@endif
              <span class="badge {{ $field->is_active ? 'ok' : 'warn' }}">{{ $field->is_active ? 'ativo' : 'desativado' }}</span>
            </div>
            <div class="field-admin-meta">
              <span class="lead-id" title="ID permanente para automações">{{ $field->field_id }}</span>
              <span>{{ $types[$field->type] ?? $field->type }}</span>
              <span>{{ ($usage[$field->id] ?? 0) }} lead(s) com dados</span>
              @if ($field->required)<span>obrigatório</span>@endif
              @if ($field->show_in_list)<span>na lista</span>@endif
            </div>
          </div>

          <form method="post" action="{{ route('member.org.fields.toggle', [$organization, $field]) }}">
            @csrf
            <button class="btn secondary small">{{ $field->is_active ? 'Desativar' : 'Reativar' }}</button>
          </form>
        </div>

        <details class="field-edit">
          <summary>Editar campo</summary>
          <form method="post" action="{{ route('member.org.fields.update', [$organization, $field]) }}">
            @csrf @method('PUT')
            <div class="grid-2">
              <label>Nome
                <input name="label" value="{{ old('label', $field->label) }}" required maxlength="120">
              </label>
              <label>Tipo
                <select name="type">
                  @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected($field->type === $value)>{{ $label }}</option>
                  @endforeach
                </select>
              </label>
              <label class="full">Opções <small>Somente para “Seleção”.</small>
                <input name="options_text" value="{{ old('options_text', implode(', ', $field->options ?? [])) }}" maxlength="2000">
              </label>
              <label class="check"><input type="checkbox" name="required" value="1" @checked($field->required)> Obrigatório no cadastro</label>
              <label class="check"><input type="checkbox" name="show_in_list" value="1" @checked($field->show_in_list)> Mostrar na lista de leads</label>
            </div>

            @if (($usage[$field->id] ?? 0) > 0)
              <div class="alert warn" style="margin-bottom:12px">
                Este campo já possui dados em <strong>{{ $usage[$field->id] }}</strong> lead(s).
                Alterar o tipo pode afetar a leitura dos valores antigos. Os dados continuam preservados.
              </div>
              <label class="check" style="margin-bottom:14px">
                <input type="checkbox" name="confirm_type_change" value="1">
                <span>Estou ciente e desejo alterar o tipo deste campo.</span>
              </label>
            @endif

            <button class="btn secondary">Salvar alterações</button>
          </form>
        </details>
      </article>
    @endforeach
  </div>
</div>
@endsection
