@php $routeBase = $routeBase ?? 'panel.'; @endphp
<div class="card">
  <h2>Membros</h2>
  <form method="post" action="{{ route($routeBase . 'members.store', $organization) }}" class="inline-form">
    @csrf
    <input type="email" name="email" placeholder="email@empresa.com" required>
    <select name="role_id" required>
      @foreach ($roles as $role)
        <option value="{{ $role->id }}">{{ $role->name }}</option>
      @endforeach
    </select>
    <button class="btn">Adicionar</button>
  </form>

  <table class="table">
    <thead><tr><th>Pessoa</th><th>Grupo</th><th>Status</th><th>Entrou</th><th></th></tr></thead>
    <tbody>
      @forelse ($memberships as $membership)
        <tr>
          <td><strong>{{ $membership->user?->name }}</strong><br><small class="muted">{{ $membership->user?->email }}</small></td>
          <td colspan="3">
            <form method="post" action="{{ route($routeBase . 'members.update', [$organization, $membership]) }}" class="inline-form">
              @csrf @method('PUT')
              <select name="role_id">
                @foreach ($roles as $role)
                  <option value="{{ $role->id }}" @selected($membership->role_id === $role->id)>{{ $role->name }}</option>
                @endforeach
              </select>
              <select name="status">
                <option value="active" @selected($membership->status === 'active')>Ativo</option>
                <option value="disabled" @selected($membership->status === 'disabled')>Desativado</option>
              </select>
              <small class="muted">{{ $membership->joined_at?->format('d/m/Y') }}</small>
              <button class="btn secondary">Salvar</button>
            </form>
          </td>
          <td class="right">
            <form method="post" action="{{ route($routeBase . 'members.destroy', [$organization, $membership]) }}" onsubmit="return confirm('Remover este membro?');">
              @csrf @method('DELETE')
              <button class="btn secondary">Remover</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted">Nenhum membro ainda.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
