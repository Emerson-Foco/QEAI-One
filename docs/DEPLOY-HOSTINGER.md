# Q One — Hostinger (referência rápida)

O guia completo de publicação, atualização, segurança e rollback está em
[`DEPLOY-GIT.md`](DEPLOY-GIT.md). Este arquivo resume apenas as decisões
específicas do hPanel.

## Estrutura

```
/home/uUSUARIO/domains/one.qeai.com.br/qone/          projeto (fora do docroot)
/home/uUSUARIO/domains/one.qeai.com.br/qone/public/   document root
```

- O projeto é clonado por Git em `qone/`.
- O docroot do subdomínio aponta para `qone/public`.
- Se o hPanel não permitir alterar o document root, abra suporte ou use um
  encaminhamento seguro. **Nunca** aponte o docroot para `qone/`.

## hPanel

1. Crie o subdomínio `one.qeai.com.br`.
2. Ative o SSL Let's Encrypt e **Forçar HTTPS**.
3. Selecione PHP 8.3 e confirme as extensões exigidas pelo instalador.
4. Crie banco e usuário MySQL exclusivos.
5. Ative o acesso SSH e cadastre sua chave pública.
6. Crie a tarefa cron:

   ```bash
   /usr/bin/php /home/uUSUARIO/domains/one.qeai.com.br/qone/artisan schedule:run >> /dev/null 2>&1
   ```

   Se o binário for diferente:

   ```bash
   /opt/alt/php83/usr/bin/php /home/uUSUARIO/domains/one.qeai.com.br/qone/artisan schedule:run >> /dev/null 2>&1
   ```

## Primeira instalação

```bash
cd ~/domains/one.qeai.com.br/qone
cp .env.example .env
nano .env
composer install --no-dev --optimize-autoloader --no-interaction
php artisan key:generate --force
chmod 600 .env
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 644 {} \;
php artisan optimize
```

Depois abra `https://one.qeai.com.br/install`, crie o ADM Root, ative o 2FA e
configure o SMTP no painel.

## SMTP

No painel Root → **Configurações**:

- Host: `smtp.hostinger.com`
- Porta: `465` (SSL/TLS) ou `587` (STARTTLS)
- Usuário: endereço completo da caixa
- Senha: senha da caixa
- Remetente: endereço válido do domínio

Use **Verificação de entrega** e confirme SPF, DKIM e DMARC no DNS.

## Atualização

```bash
cd ~/domains/one.qeai.com.br/qone
mysqldump --single-transaction --quick -h DB_HOST -u DB_USER -p DB_NAME \
  > ~/backups/qone-$(date +%F-%H%M).sql

php artisan down --secret="CODIGO_TEMPORARIO"
git fetch origin
git reset --hard origin/main
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize
php artisan up
```

Não use `git clean -fdx`: isso apagaria `.env`, `vendor/` e `storage/`.

## Limitações da hospedagem compartilhada

- Sem Redis/WebSocket; inbox em tempo real e filas persistentes ficam limitados.
- Cron mínimo de 1 minuto.
- Envio de e-mail depende do SMTP e da reputação do domínio.
- Para volume alto, webhooks concorrentes, IMAP e automações, migrar para VPS.

Essas limitações também estão em [`SECURITY.md`](SECURITY.md) e no plano técnico.
