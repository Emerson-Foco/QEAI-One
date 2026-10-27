# Q One — Segurança

Checklist e regras de segurança da plataforma. Este documento é a referência para
produção e para qualquer alteração futura.

---

## 1. Princípios

1. **Autorização no backend.** Esconder botões no front não é controle de acesso.
2. **Escopo por organização.** Toda leitura/escrita de dados de negócio usa o
   `organization_id` do contexto autenticado, nunca um ID enviado pelo cliente.
3. **Menor privilégio.** Permissões são concedidas por grupo; o padrão é negar.
4. **Root não ignora a governança de dados.** `OrgAccess::canData()` exige vínculo
   com a organização; `OrgAccess::can()` é usado apenas para ações administrativas.
5. **Segredos no servidor.** Credenciais SMTP, tokens e chaves nunca vão ao front.

---

## 2. Controles implementados

### Autenticação
- Login por e-mail e senha com hash `bcrypt`.
- Rate limit de login (5 tentativas / 15 min por e-mail + IP).
- 2FA TOTP opcional, com rate limit e segredo criptografado.
- Verificação de e-mail por link assinado e temporário.
- Recuperação de senha com token com hash, validade de 60 min e rate limit.
- Sessão regenerada após login e alteração de senha.

### Autorização
- `EnsureMember`, `EnsureOrganizationAccess`, `EnsureRootAdmin`.
- Permissões `org.*` verificadas em todas as ações de organização.
- Dados de CRM/atendimento exigem `OrgAccess::canData()` — Root sem vínculo recebe
  tela de governança ou 403.
- O Root sem vínculo vê apenas **eventos administrativos** de auditoria (membros,
  grupos, convites, plano), nunca eventos de leads/atendimento.
- Webhooks de saída têm segredo criptografado e bloqueio de destinos internos/privados (SSRF).
- WhatsApp exige `app_secret` e valida a assinatura HMAC em cada recebimento.
- Chat público tem rate limit por IP/canal.
- Módulos desativados são bloqueados por middleware (`module`) inclusive por URL direta.
- Preferências de visão são sempre do usuário autenticado + organização da rota.

### Entrada e saída
- CSRF global em rotas web; API/webhooks têm autenticação própria.
- Validação server-side com `FormRequest`/`$request->validate()`.
- Saída HTML escapada pelo Blade; `{!! !!}` apenas com conteúdo controlado.
- URL de campo personalizado só vira link se começar com `http://` ou `https://`.
- Campos personalizados com tipo, opções e validação compatíveis.
- CSV de logs neutraliza fórmulas (`=`, `+`, `-`, `@`).

### Dados e termos`n- [ ] `LEGAL_REQUIRE_ACCEPTANCE=true` em produ��o`n- [ ] Termos de Uso publicados em `https://qeai.com.br/termos` e infraestrutura
- Auditoria com encadeamento de hash em escopos `platform` e `organization`.
- Credenciais de canais/SMTP criptografadas.
- Tokens de API armazenados como hash; escopo por organização e rate limit.
- Webhooks com segredo e assinatura HMAC quando configurados.
- Instalador bloqueado após a instalação (`storage/app/installed.json`).
- Diretórios internos não são servidos publicamente no deploy recomendado
  (`public/` como docroot).
- Headers: CSP, `X-Frame-Options: DENY`, `nosniff`, Referrer-Policy,
  Permissions-Policy, COOP e HSTS em HTTPS.
- `Content-Security-Policy` restringe scripts, estilos e frames; `unsafe-inline`
  é usado apenas onde o layout atual exige.
- Sessões com `HttpOnly` e `SameSite=Lax`; `Secure` em HTTPS.

### Multi-tenancy
- Leads, campos, preferências, logs, canais, conversas, formulários, social e anúncios
  são filtrados por organização.
- Rotas aninhadas validam que o registro pertence à organização da URL.
- Testes de isolamento cobrem leads, campos, movimentação de Kanban e preferências.

---

## 3. Checklist de produção

### Aplicação
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` exclusiva e com backup seguro
- [ ] `APP_URL` com HTTPS
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `SESSION_ENCRYPT=true`
- [ ] `SESSION_SAME_SITE=lax`
- [ ] `TRUSTED_PROXIES` configurado se houver proxy/CDN
- [ ] Cache de configuração/rotas/views gerado no deploy
- [ ] Cron `schedule:run` ativo
- [ ] Queue configurada (database) e worker/cron conforme hospedagem
- [ ] Migrações executadas com `--force`
- [ ] SMTP da plataforma testado
- [ ] `storage/` e `bootstrap/cache/` graváveis apenas pelo necessário

### Banco e servidor
- [ ] Usuário MySQL com permissões mínimas
- [ ] Banco não exposto publicamente
- [ ] Backup automático e teste de restauração
- [ ] Docroot apontando para `public/`
- [ ] Arquivos `.env`, `storage`, `vendor` e `docs` fora do acesso público
- [ ] HTTPS válido e redirecionamento HTTP → HTTPS
- [ ] PHP 8.2+ com extensões exigidas
- [ ] Logs de erro fora da pasta pública
- [ ] Limite de upload e timeout coerentes

### Dados e termos`n- [ ] `LEGAL_REQUIRE_ACCEPTANCE=true` em produ��o`n- [ ] Termos de Uso publicados em `https://qeai.com.br/termos`
- [ ] Nenhum usuário/QA/demo de teste no banco de produção
- [ ] Nenhum lead/demo de teste
- [ ] Organizações reais revisadas
- [ ] Planos e limites conferidos
- [ ] Campos padrão revisados por organização
- [ ] Permissões dos grupos revisadas
- [ ] Root com 2FA ativo
- [ ] SMTP com remetente válido e SPF/DKIM/DMARC

### Publicação
- [ ] `docs/` não é servido publicamente
- [ ] Nenhum texto de teste, debug ou instrução interna nas telas
- [ ] Assets com cache versionado
- [ ] Favicon e metadados revisados
- [ ] Política de privacidade e termos publicados
- [ ] Canal de denúncia de convite funcionando
- [ ] Rotina de resposta a incidentes definida

---

## 4. Riscos residuais e próximos passos

- **Hospedagem compartilhada:** sem WAF dedicado, Redis, filas persistentes ou
  isolamento de processos. Para escala, migrar para VPS.
- **E-mail:** entregabilidade depende de SPF/DKIM/DMARC e do provedor SMTP.
- **Anexos e mídias:** validar MIME real e limites antes de habilitar uploads no CRM.
- **IMAP:** ao implementar recebimento, validar host/porta para evitar SSRF.
- **Retenção:** definir política de retenção de leads, logs e backups (LGPD).
- **Auditoria:** revisar periodicamente acessos Root e permissões de grupos.
- **Testes de intrusão:** recomendados antes de campanhas de alto volume.

---

## 5. Validação executada

A suíte cobre autenticação, organizações, permissões, convites, limites de plano,
CRM, campos personalizados, leads, visões, preferências, módulos, API, webhooks,
inbox, social, anúncios, segurança de headers, CSV e isolamento entre organizações.

- **126 testes automatizados / 539 asserções** executados com sucesso.
- **44 verificações do site institucional** executadas com sucesso.
- Verificações específicas: headers/CSP, XSS em URL, CSV injection, SSRF de webhooks,
  preferências por usuário/organização, isolamento de leads e campos, governança do
  Root sem vínculo e assinatura de webhooks.

Para executar localmente:

```bash
php artisan test
```

Não publicar resultados de teste, credenciais ou dados de demonstração na interface.
