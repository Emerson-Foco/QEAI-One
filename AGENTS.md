# Instruções do projeto Q One

Leia antes de alterar a plataforma:

- `docs/DESIGN-SYSTEM.md` — padrão visual, componentes, motion e regras de UI.
- `docs/SECURITY.md` — checklist e regras de segurança.
- `docs/DEPLOY-HOSTINGER.md` — publicação, cron, SMTP e validação pós-deploy.
- `docs/CHECKLIST.md` — estado atual do produto e roadmap.

Regras essenciais:

1. Regras de negócio e autorização ficam no backend; nunca apenas no front.
2. Toda consulta de dados de negócio deve ser escopada por organização.
3. Preserve `OrgAccess::canData()` para dados de negócio e `can()` para ações administrativas.
4. Não publique textos de teste, demonstração ou debug na interface.
5. Não adicione dependências de UI sem necessidade; use o design system existente.
6. CSRF, rate limit, auditoria, validação server-side e headers de segurança são obrigatórios.
7. Rode a suíte de testes após alterações e mantenha as migrações atualizadas.
