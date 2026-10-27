# QEAI One — Checklist do projeto

Legenda: `[x]` concluído e testado · `[~]` em andamento · `[ ]` pendente

> Regra de trabalho: **fechar cada etapa por completo antes de avançar** para a próxima.

---

## Fase 0 — Fundação

### Ambiente e deploy
- [x] Composer local, Laravel 12, MySQL (`qeai_one`), Git
- [x] Repositório Git local (branch `main`) com commits por etapa
- [ ] Repositório no GitHub e push
- [ ] Deploy em `one.qeai.com.br` (docroot/link simbólico, `.env`, cron do agendador)
- [ ] Ambiente de homologação (`homolog.qeai.com.br`) com banco separado

### Instalação e acesso
- [x] Instalador guiado estilo WordPress (requisitos → banco → ADM Root → identidade)
- [x] Login por e-mail, logout e rate limit
- [x] Marcador de instalação + middleware `installed`
- [x] Correção do ADM Root (flag fora do mass assignment)
- [x] **Recuperação de senha por e-mail** (link com validade de 1h, rate limit, auditoria)
- [x] **2FA (TOTP)** — ativação com QR/segredo, desafio no login, desativação com senha
- [x] **Verificação de e-mail** — ADM verificado na instalação; convidado verificado ao aceitar; reenvio com link assinado
- [x] Página **Segurança** no painel (senha, 2FA, situação do e-mail)
- [x] **Auto-cadastro B2B**: página pública `/cadastro` cria a organização + proprietário e entra direto

### Multi-tenancy e RBAC
- [x] Organizações, membros e grupos de acesso (permissões configuráveis)
- [x] Auditoria em dois escopos (`platform` / `organization`) com encadeamento de hash
- [x] Convites com **consentimento obrigatório**, **rate limit**, link de recusa (antifraude)
- [x] Área do membro (`/app`) e redirecionamento de login por tipo de usuário
- [x] Configurações da plataforma: identidade (white label) + SMTP + teste de envio
- [x] **Painel da organização** para membros (`/app/organizacoes/{org}`), escopado por organização
- [x] **Enforcement de `org.*`** nas ações (membros, convites, grupos, logs) + seletor de organização (multi-org)
- [x] **Multi-organização**: usuário com várias organizações, permissões por organização e aceite de novo convite sem trocar a senha
- [x] **Overrides de recurso/limite por organização** (`organização > plano > padrão`)
- [x] **Aplicar limites do plano** (bloqueia adicionar membro e aceitar convite ao exceder o limite de usuários)

### Planos
- [x] Planos dinâmicos (recursos e limites), plano **free** semeado
- [x] Atribuição de plano à organização + auditoria
- [ ] Integração de cobrança (Asaas) — adiada
- [ ] Tela pública de planos/pricing (site)

### Logs e governança
- [x] Trilha de auditoria registrando ações da plataforma e da organização
- [x] Tela de **logs da plataforma** (filtros por ação/período/IP + paginação + exportação CSV)
- [x] Tela de **logs da organização** (filtros + exportação CSV)
- [x] Alerta por e-mail aos ADMs quando um convite é reportado como não reconhecido (+ aviso no painel)
- [ ] Política de retenção de dados e de logs

### Documentos legais
- [ ] Termos de Uso
- [ ] Política de Privacidade e de Cookies
- [ ] Termo de Tratamento de Dados (DPA) — controlador/operador
- [ ] Lista pública de subprocessadores
- [ ] Encarregado (DPO) com contato público

---

### Design e UX
- [x] **Design system v2** em `docs/DESIGN-SYSTEM.md` (tokens, sidebar, dashboard, auth dividido, ícones, responsivo)
- [x] **Login e cadastro em tela dividida** com marca, benefícios e card de formulário
- [x] **Sidebar escura** com navegação enxuta: Dashboard, Leads, Gestão de CRM, Configurações e Logs
- [x] **Dashboard focado em leads**: total, novos no período, campos ativos, origem principal, donut e leads recentes
- [x] Acesso do **Root** ao painel da organização com tela de governança quando não há vínculo

### Núcleo atual — Leads e Gestão de CRM
- [x] **ID permanente do lead** (`LD-...`) gerado no cadastro, usado na busca, no histórico e na API/webhooks
- [x] **Lista de leads** com **4 visões**: Tabela, Kanban, Calendário e Cronograma
- [x] **Kanban** agrupado por campo de seleção, com arrastar-e-soltar e seletor de movimentação
- [x] **Calendário** por campo de data, com navegação mensal
- [x] **Cronograma** com campos de início/fim e janela de 3 meses
- [x] **Filtros por campo** (seleção, data, sim/não, número e texto) compartilhados entre as visões
- [x] **Cadastro/edição de lead** com campos fixos + campos personalizados ativos
- [x] **Detalhe do lead** com dados, campos (inclusive desativados) e **histórico** de movimentações
- [x] **Gestão de CRM** com criação de campos (nome, tipo, opções, obrigatório, mostrar na lista)
- [x] **ID permanente do campo** (`FLD-...`) para automações, estável ao renomear ou alterar o tipo
- [x] Campos **não podem ser excluídos**: apenas desativados/reativados, preservando o histórico
- [x] Alteração de **tipo** exige confirmação quando o campo já possui dados
- [x] Permissão dedicada **`org.crm_fields`** para configurar o CRM
- [x] **Módulos** controlados por `config/modules.php` (`QEAI_MODULES`): não-core ocultos da navegação
- [x] **Isolamento por organização** validado em testes para leads, campos e permissões

## Fase 1 — MVP operacional (CRM + atendimento)
- [x] **CRM: contatos + empresas** (escopo por organização, permissão `org.leads`, tags, auditoria)
- [x] CRM: pipelines/negócios (kanban por etapas, valor, ganho/perdido) e tarefas/atividades
- [x] **Captação: formulários personalizados** (com campos customizados) + página pública + incorporação por iframe
- [x] **API pública de captação** com chave por organização (`POST /api/v1/leads`, `X-Api-Key`)
- [x] **Webhooks de saída (triggers)** assinados (HMAC) ao criar lead — estilo N8N/Zapier/Make
- [x] Consentimento (LGPD) + honeypot + rate limit na captação
- [x] **Webhooks de entrada**: endpoint genérico (`/hooks/{token}`) + **Meta Lead Ads** (`/hooks/meta/{token}` com verify token, app secret e busca no Graph API) usando credenciais do cliente
- [x] **Inbox unificado**: chat no site (widget/iframe) + canal de e-mail (envio via SMTP) + caixa unificada (status, atribuição, vínculo com o contato)
- [ ] **Recebimento de e-mail (IMAP)** para respostas caírem no inbox automaticamente (envio já funciona)
- [x] **Notificações** in-app (sino) + e-mail: novo lead, nova mensagem e tarefa vencendo (comando agendado)
- [x] **Onboarding da organização** (checklist de primeiros passos no painel da organização)
- [x] **Campos personalizados** (defaults Status/Temperatura + campos próprios) e **visões** de contatos (Tabela/Quadro agrupado por campo)
- [x] **Templates de campos pelo ADM Root** (template padrão para novas organizações + aplicar a organizações existentes)
- [ ] Visões extras (calendário, galeria) e filtros salvos

## Fase 2 — Chatbot + WhatsApp
- [x] **WhatsApp Cloud API** (credenciais da própria organização): webhook de verificação/recebimento + envio pelo inbox
- [ ] Construtor de fluxo (sem IA)
- [ ] Base de conhecimento + IA (opcional) com transbordo humano

## Fase 3 — Social + Ads
- [x] **Publicação em redes sociais**: contas, compositor, **agendamento** e publicador (webhook de automação ou página do Facebook)
- [x] **Anúncios**: contas, importação de métricas por webhook, lançamento manual e **relatório** (investimento, CTR, CPC, CPA)

## Fase 4 — Canais extras + modo agência
- [ ] SMS e voz
- [ ] Modo agência (QEAI operando dentro da conta do cliente)
- [ ] Analytics avançado e app mobile

## Fase 5 — Endurecimento e escala
- [ ] RIPD/DPIA, plano de resposta a incidentes (ANPD, 3 dias úteis)
- [ ] Pentest e revisão de segurança
- [ ] Migração/otimização para VPS quando viável

---

## Notas de infraestrutura (hospedagem compartilhada)
- Fila: driver `database` + `schedule:run` (cron de 1 min).
- Cache/sessão: `database` (sem Redis).
- E-mail: SMTP configurável pelo Root; provedor transacional no futuro, se o volume exigir.
- Tempo real: polling no compartilhado; WebSocket só em VPS.
