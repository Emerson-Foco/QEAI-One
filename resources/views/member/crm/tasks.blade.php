@extends('layouts.member')

@section('title', 'Tarefas — ' . $organization->name)

@section('panel')
<div class="page-head">
  <div>
    <span class="pill">CRM</span>
    <h1>Tarefas</h1>
  </div>
  <form method="get" class="inline-form">
    <select name="status" onchange="this.form.submit()" aria-label="Filtrar por status">
      <option value="">Todas</option>
      <option value="open" @selected($status === 'open')>Abertas</option>
      <option value="done" @selected($status === 'done')>Concluídas</option>
    </select>
  </form>
</div>

<div class="card">
  <h2>Nova tarefa</h2>
  <form method="post" action="{{ route('member.org.tasks.store', $organization) }}">
    @csrf
    <div class="grid-2">
      <label>Título<input name="title" required maxlength="180"></label>
      <label>Vencimento<input type="datetime-local" name="due_at"></label>
      <label>Contato<select name="contact_id"><option value="">—</option>@foreach ($contacts as $contact)<option value="{{ $contact->id }}">{{ $contact->name }}</option>@endforeach</select></label>
      <label>Negócio<select name="deal_id"><option value="">—</option>@foreach ($deals as $deal)<option value="{{ $deal->id }}">{{ $deal->title }}</option>@endforeach</select></label>
      <label class="full">Observações<textarea name="notes" rows="2" maxlength="5000"></textarea></label>
    </div>
    <button class="btn">Criar tarefa</button>
  </form>
</div>

<div class="card">
  <table class="table">
    <thead><tr><th>Status</th><th>Tarefa</th><th>Vínculo</th><th>Vencimento</th><th></th></tr></thead>
    <tbody>
      @forelse ($tasks as $task)
        <tr>
          <td><span class="badge">{{ $task->status === 'done' ? 'Concluída' : 'Aberta' }}</span></td>
          <td class="{{ $task->status === 'done' ? 'task-done' : '' }}">
            <strong>{{ $task->title }}</strong>
            @if ($task->notes)<br><small class="muted">{{ \Illuminate\Support\Str::limit($task->notes, 90) }}</small>@endif
          </td>
          <td>
            @if ($task->contact){{ $task->contact->name }}@endif
            @if ($task->deal){{ $task->contact ? ' · ' : '' }}{{ $task->deal->title }}@endif
            @if (! $task->contact && ! $task->deal)—@endif
          </td>
          <td>{{ $task->due_at?->format('d/m/Y H:i') ?? '—' }}</td>
          <td class="right">
            <div class="row-actions inline">
              <form method="post" action="{{ route('member.org.tasks.toggle', [$organization, $task]) }}">
                @csrf
                <button class="btn secondary small">{{ $task->status === 'open' ? 'Concluir' : 'Reabrir' }}</button>
              </form>
              <form method="post" action="{{ route('member.org.tasks.destroy', [$organization, $task]) }}" onsubmit="return confirm('Remover tarefa?');">
                @csrf @method('DELETE')
                <button class="btn secondary small">Excluir</button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted">Nenhuma tarefa.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if ($tasks->hasPages())
    <nav class="pagination">
      @if ($tasks->onFirstPage())<span class="pagination-link is-disabled">← Anterior</span>@else<a class="pagination-link" href="{{ $tasks->previousPageUrl() }}">← Anterior</a>@endif
      <span class="pagination-status">Página {{ $tasks->currentPage() }} de {{ $tasks->lastPage() }}</span>
      @if ($tasks->hasMorePages())<a class="pagination-link" href="{{ $tasks->nextPageUrl() }}">Próxima →</a>@else<span class="pagination-link is-disabled">Próxima →</span>@endif
    </nav>
  @endif
</div>
@endsection
