# Q One — Deploy na Hostinger

Checklist operacional para publicar o Q One em `https://one.qeai.com.br` em
hospedagem compartilhada PHP/MySQL.

> Este documento não contém segredos. Use o gerenciador de senhas da QEAI para
> armazenar `APP_KEY`, senha do banco e credenciais SMTP.

---

## 1. Pré-requisitos

- Subdomínio `one.qeai.com.br` criado na Hostinger.
- PHP **8.3** (mínimo 8.2) com `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`,
  `xml`, `ctype`, `json`, `fileinfo` e `curl`.
- Banco MySQL/MariaDB exclusivo (não reutilizar base de outro sistema).
- SSL ativo (Let's Encrypt) e HTTPS forçado.
- Cron da Hostinger disponível.
- Site institucional publicado com `/termos` e `/privacidade` acessíveis.

---

## 2. Estrutura de diretórios

O docroot do subdomínio deve apontar para a pasta **`public/`** do projeto.

```
/home/USUARIO/domains/one.qeai.com.br/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/          ← document root
├── resources/
├── routes/
├── storage/
├── vendor/
├── artisan
├── composer.json
└── .env             ← fora do docroot
```

Se a Hostinger não permitir alterar o document root, use um diretório
`public_html` apenas com um `.htaccess`/`index.php` encaminhando para `public/`
ou abra suporte para ajustar o docroot. **Nunca** aponte o docroot para a raiz do
projeto, pois `.env` e `storage/` ficariam expostos.

---

## 3. Arquivo `.env` de produção

Crie `.env` na raiz (nunca versionar) com este modelo:

```env
APP_NAME="Q One"
APP_ENV=production
APP_KEY=base64:GERAR_UMA_CHAVE_UNICA
APP_DEBUG=false
APP_URL=https://one.qeai.com.br
APP_TIMEZONE=America/Sao_Paulo

APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=USUARIO_QONE
DB_USERNAME=USUARIO_QONE
DB_PASSWORD=SENHA_FORTE

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=sync

FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS="nao-responda@qeai.com.br"
MAIL_FROM_NAME="Q One"

QEAI_MODULES=dashboard,leads,crm_fields,settings,logs

LEGAL_TERMS_URL=https://qeai.com.br/termos
LEGAL_PRIVACY_URL=https://qeai.com.br/privacidade
LEGAL_REQUIRE_ACCEPTANCE=true
```

Observações:
- `APP_KEY` deve ser gerada uma única vez e guardada em local seguro.
- SMTP real é configurado no painel Root (Configurações), não no `.env`.
- `QUEUE_CONNECTION=sync` evita jobs pendentes sem worker na hospedagem compartilhada.
- `SESSION_ENCRYPT=true` criptografa o conteúdo da sessão no banco.

---

## 4. Envio dos arquivos

1. Gere o build local com `vendor/` já instalado (`composer install --no-dev --optimize-autoloader`).
2. Envie o projeto para a Hostinger via Git, SSH, FTP ou Gerenciador de Arquivos.
3. Não envie:
   - `.env` local;
   - `storage/logs/*`, `storage/framework/cache/*`, `storage/framework/sessions/*`;
   - `tests/`, `docs/`, `node_modules/`, `.git/`;
   - bancos SQLite locais.
4. Crie `.env` no servidor com os valores de produção.
5. Permissões:
   - `storage/` e `bootstrap/cache/` → `775` (ou `755` se o host exigir);
   - arquivos → `644`;
   - nunca `777`.

---

## 5. Banco e instalação

1. Crie banco e usuário MySQL exclusivos no hPanel.
2. Acesse `https://one.qeai.com.br/install`.
3. O instalador verifica requisitos, testa o banco, roda as migrations, cria o
   **ADM Root** e grava a identidade da plataforma.
4. Após concluir, o instalador fica bloqueado (`storage/app/installed.json`).
5. Entre com o ADM Root e conclua o 2FA.

Se usar SSH, o fluxo alternativo é:

```bash
php artisan migrate --force
php artisan optimize
```

Mas o instalador continua sendo o caminho recomendado para criar o primeiro ADM.

---

## 6. Otimização e permissões

Com SSH:

```bash
php artisan optimize
php artisan storage:link
```

Sem SSH, execute as rotas equivalentes via painel/terminal da Hostinger ou
envie o projeto já otimizado. Após qualquer alteração de `.env`:

```bash
php artisan optimize:clear
php artisan optimize
```

---

## 7. Cron

Configure **a cada minuto** no hPanel:

```bash
/usr/bin/php /home/USUARIO/domains/one.qeai.com.br/artisan schedule:run >> /dev/null 2>&1
```

Se o caminho do PHP for diferente, use o binário indicado pela Hostinger
(ex.: `/opt/alt/php83/usr/bin/php`).

---

## 8. SMTP e e-mails

No painel Root → **Configurações**:
1. Preencha host, porta, criptografia, usuário e senha SMTP.
2. Salve.
3. Use **Verificação de entrega** para enviar um e-mail de teste.
4. Confirme SPF, DKIM e DMARC no DNS do domínio.
5. Teste também convite e recuperação de senha.

Sem SMTP configurado, convites e redefinições de senha não são enviados.

---

## 9. Pós-deploy — validação obrigatória

- [ ] `https://one.qeai.com.br/login` abre com HTTPS válido.
- [ ] `APP_DEBUG=false` e erros não aparecem na tela.
- [ ] `/install` retorna 404 após a instalação.
- [ ] Login do ADM Root funciona.
- [ ] 2FA do Root está ativo.
- [ ] Cadastro público cria organização e aceita os termos.
- [ ] Convite chega por e-mail e abre a tela de aceite.
- [ ] Organização, membros, grupos e permissões funcionam.
- [ ] Lead cria ID permanente e registra histórico.
- [ ] Campos personalizados criam, editam, desativam e reativam.
- [ ] Visões Tabela, Kanban, Calendário e Cronograma funcionam.
- [ ] Preferência de filtros salva e recarrega por visão.
- [ ] API de leads responde com chave válida e rejeita sem chave.
- [ ] Webhooks de saída usam HTTPS e assinatura.
- [ ] `/termos` e `/privacidade` estão publicados.
- [ ] Usuário sem aceite é direcionado para `/termos/aceite`.
- [ ] Backup do banco e do `.env` está configurado.
- [ ] Logs de erro estão fora do docroot.
- [ ] Nenhum usuário ou dado de teste está no banco.

---

## 10. Rollback

1. Ative manutenção: `php artisan down`.
2. Restaure o backup do banco.
3. Restaure a versão anterior do código.
4. `php artisan optimize:clear && php artisan optimize`.
5. `php artisan up`.

Mantenha sempre um backup antes de migrations destrutivas.

---

## 11. Limitações da hospedagem compartilhada

- Sem Redis/WebSocket; inbox em tempo real e filas persistentes ficam limitados.
- Cron mínimo de 1 minuto.
- Envio de e-mail depende do SMTP e da reputação do domínio.
- Para volume alto, webhooks concorrentes, IMAP e automações, migrar para VPS.

Essas limitações estão documentadas em `docs/SECURITY.md` e no plano técnico.
