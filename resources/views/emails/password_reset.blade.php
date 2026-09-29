<!doctype html>
<html lang="pt-BR">
<body style="font-family:system-ui,-apple-system,'Segoe UI',sans-serif;color:#24253B;line-height:1.6;background:#F7F6FB;padding:24px">
  <div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #E6E4EE;border-radius:16px;padding:28px">
    <h2 style="margin-top:0">Redefinição de senha</h2>
    <p>Olá, {{ $user->name }}. Recebemos um pedido para redefinir sua senha no QEAI One.</p>
    <p><a href="{{ $link }}" style="display:inline-block;background:#6D3DF5;color:#ffffff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:600">Definir nova senha</a></p>
    <p style="color:#66677A;font-size:13px">Este link vale por 1 hora. Se não foi você, ignore este e-mail.</p>
  </div>
</body>
</html>
