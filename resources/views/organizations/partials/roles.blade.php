@php $routeBase = $routeBase ?? 'panel.'; @endphp
<div class="card">
  <h2>Grupos de acesso</h2>
  <p class="muted">Cada grupo define o que o membro pode fazer na organização.</p>

  @foreach ($roles as $role)
    @php $rolePermissions = $role->permissions ?? []; @endphp
    <div class="role-block">
      <form method="post" action="{{ route($routeBase . 'roles.update', [$organization, $role]) }}">
        @csrf @method('PUT')
        <div class="role-head">
          <input name="name" value="{{ $role->name }}" maxlength="80" aria-label="Nome do grupo">
          @if ($role->is_system) <span class="badge">padrão</span> @endif
        </div>
        <div class="perm-grid">
          @foreach ($permissions as $key => $label)
            <label class="check"><input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array('*', $rolePermissions, true) || in_array($key, $rolePermissions, true))> {{ $label }}</label>
          @endforeach
        </div>
        <button class="btn secondary">Salvar grupo</button>
      </form>
      @unless ($role->is_system)
        <form method="post" action="{{ route($routeBase . 'roles.destroy', [$organization, $role]) }}" onsubmit="return confirm('Excluir este grupo?');">
          @csrf @method('DELETE')
          <button class="btn secondary">Excluir grupo</button>
        </form>
      @endunless
    </div>
  @endforeach

  <div class="role-block new-role">
    <form method="post" action="{{ route($routeBase . 'roles.store', $organization) }}">
      @csrf
      <div class="role-head"><input name="name" placeholder="Nome do novo grupo" maxlength="80" required></div>
      <div class="perm-grid">
        @foreach ($permissions as $key => $label)
          <label class="check"><input type="checkbox" name="permissions[]" value="{{ $key }}"> {{ $label }}</label>
        @endforeach
      </div>
      <button class="btn">Criar grupo</button>
    </form>
  </div>
</div>
