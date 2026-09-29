<!doctype html>
<html lang="pt-BR">
<body style="font-family:system-ui,-apple-system,'Segoe UI',sans-serif;color:#24253B;line-height:1.6;background:#F7F6FB;padding:24px">
  <div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #E6E4EE;border-radius:16px;padding:28px">
    <h2 style="margin-top:0">{{ $title }}</h2>
    <p>{{ $body }}</p>
    @if ($url)
      <p><a href="{{ $url }}" style="display:inline-block;background:#6D3DF5;color:#ffffff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:600">Abrir no QEAI One</a></p>
    @endif
  </div>
</body>
</html>
