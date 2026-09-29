<!doctype html>
<html lang="pt-BR">
<body style="font-family:system-ui,-apple-system,'Segoe UI',sans-serif;color:#24253B;line-height:1.6;background:#F7F6FB;padding:24px">
  <div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #E6E4EE;border-radius:16px;padding:28px">
    <h2 style="margin-top:0">Convite para {{ $organization->name }}</h2>
    <p>Você foi convidado(a) para participar da organização <strong>{{ $organization->name }}</strong> no <strong>QEAI One</strong>, com o grupo de acesso <strong>{{ $role?->name ?? 'Membro' }}</strong>.</p>
    <p><a href="{{ $acceptUrl }}" style="display:inline-block;background:#6D3DF5;color:#ffffff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:600">Aceitar convite</a></p>
    <p style="color:#66677A;font-size:13px">Este link vale por 7 dias e só pode ser usado uma vez.</p>
    <hr style="border:0;border-top:1px solid #E6E4EE;margin:22px 0">
    <p style="color:#66677A;font-size:12px">
      Não reconhece este convite? <a href="{{ $reportUrl }}">Informe aqui</a> e registraremos para análise.
      Caso não reconheça, não clique em "Aceitar convite".
    </p>
  </div>
</body>
</html>
