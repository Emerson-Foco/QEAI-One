@php $routeBase = $routeBase ?? 'panel.'; @endphp
<div class="card">
  <h2>Convites</h2>
  @if (session('invite_link'))
    <div class="alert ok">SMTP não configurado. Copie o link e envie manualmente:<br><code style="word-break:break-all">{{ session('invite_link') }}</code></div>
  @endif
  <p class="muted">Ao enviar, você declara que tem a autorização e o consentimento desta pessoa para receber o convite.</p>
  <form method="post" action="{{ route($routeBase . 'invites.store', $organization) }}" class="inline-form">
    @csrf
    <input type="email" name="email" placeholder="email@empresa.com" required>
    <select name="role_id" required>
      @foreach ($roles as $role)
        <option value="{{ $role->id }}">{{ $role->name }}</option>
      @endforeach
    </select>
    <button class="btn">Enviar convite</button>
    <label class="check" style="flex-basis:100%">
      <input type="checkbox" name="consent" value="1" required>
      Declaro que tenho a autorização e o consentimento desta pessoa para enviar o convite.
    </label>
  </form>

  <table class="table">
    <thead><tr><th>E-mail</th><th>Grupo</th><th>Status</th><th>Enviado</th><th>Expira</th><th></th></tr></thead>
    <tbody>
      @forelse ($invites as $invite)
        <tr>
          <td>{{ $invite->email }}</td>
          <td>{{ $invite->role?->name ?? '—' }}</td>
          <td>
            <span class="badge">
              @switch($invite->status)
                @case('pending') Pendente @break
                @case('accepted') Aceito @break
                @case('revoked') Revogado @break
                @case('reported') Denunciado @break
              @endswitch
            </span>
          </td>
          <td>{{ $invite->created_at?->format('d/m/Y H:i') }}</td>
          <td>{{ $invite->expires_at?->format('d/m/Y') }}</td>
          <td class="right">
            @if ($invite->status === 'pending')
              <form method="post" action="{{ route($routeBase . 'invites.destroy', [$organization, $invite]) }}" onsubmit="return confirm('Revogar este convite?');">
                @csrf @method('DELETE')
                <button class="btn secondary">Revogar</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="muted">Nenhum convite enviado.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
