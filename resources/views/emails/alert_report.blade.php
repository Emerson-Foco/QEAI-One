<!doctype html>
<html lang="pt-BR">
<body style="font-family:system-ui,-apple-system,'Segoe UI',sans-serif;color:#24253B;line-height:1.6;background:#F7F6FB;padding:24px">
  <div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #E6E4EE;border-radius:16px;padding:28px">
    <h2 style="margin-top:0;color:#9E342C">Convite reportado como não reconhecido</h2>
    <p>Uma pessoa informou que <strong>não reconhece</strong> um convite enviado pela organização <strong>{{ $organization }}</strong>.</p>
    <ul>
      <li>E-mail convidado: <strong>{{ $invite->email }}</strong></li>
      <li>Enviado em: {{ $invite->created_at?->format('d/m/Y H:i') }}</li>
      <li>Observação: {{ $invite->report_note ?: '—' }}</li>
    </ul>
    <p>O convite foi bloqueado automaticamente. Revise a organização e os convites enviados, pois isso pode indicar uso indevido da plataforma.</p>
  </div>
</body>
</html>
