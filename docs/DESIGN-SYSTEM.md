# QEAI One — Design System

Padrão visual oficial da plataforma. Objetivo: **consistência, clareza e facilidade de uso**
para qualquer pessoa com conhecimento básico de informática.

- CSS: `public/css/app.css`
- JavaScript de interação: `public/js/app.js`
- Ícones: componente Blade `<x-icon name="..." />` (`resources/views/components/icon.blade.php`)
- Layouts: `layouts/app`, `layouts/auth`, `layouts/member`, `layouts/panel`
- Referência visual: dashboard com **sidebar escura**, cards claros, KPIs e gráficos;
  autenticação em **tela dividida** (marca à esquerda, formulário à direita).

> **Escopo atual do produto:** Dashboard, Leads e Gestão de CRM, além de autenticação,
> organização, equipe/permissões e logs. Os demais módulos estão preservados no código,
> mas ocultos da navegação até serem reativados em `config/modules.php`.

---

## 1. Princípios

1. **Simplicidade primeiro** — cada tela deve deixar claro o próximo passo. Evite telas densas.
2. **Consistência** — reutilize tokens e componentes existentes; não crie variações soltas.
3. **Hierarquia** — título → contexto → ação principal → conteúdo. Uma ação primária por bloco.
4. **Acessibilidade** — contraste, foco visível (`:focus-visible`), navegação por teclado,
   textos alternativos e `aria-label` em botões com ícone.
5. **Sem dependências** — HTML/CSS/JS puro, sem Node, Vite ou bibliotecas de UI.

---

## 2. Tokens

Definidos em `:root` no `public/css/app.css`. A cor primária é **white label**: o ADM Root
define `primary_color` em **Configurações** e o layout injeta `--primary` no `<head>`.

| Token | Valor padrão | Uso |
|---|---|---|
| `--primary` | `#6D3DF5` | Ações principais, links, item ativo |
| `--primary-600` | `#5B2EE8` | Hover / estados mais escuros |
| `--primary-soft` | `#F1EDFE` | Fundos suaves, badges, ícones |
| `--secondary` | `#2F80FF` | Apoio, gráficos, gradientes |
| `--accent` | `#F97316` | Notificações, alertas de atenção |
| `--dark` | `#0B1220` | Sidebar, títulos e textos fortes |
| `--ink` | `#1E293B` | Texto padrão |
| `--muted` | `#64748B` | Texto secundário |
| `--border` | `#E6EAF2` | Bordas, divisores |
| `--surface` | `#FFFFFF` | Cards e superfícies |
| `--soft` | `#F5F7FC` | Fundo da aplicação |
| `--ok` / `--ok-bg` | `#15803D` / `#ECFDF3` | Sucesso |
| `--warn` / `--warn-bg` | `#B45309` / `#FFF7ED` | Atenção |
| `--err` / `--err-bg` | `#DC2626` / `#FEF2F2` | Erro / ação destrutiva |
| `--info` / `--info-bg` | `#2563EB` / `#EFF6FF` | Informação |
| `--radius` | `16px` | Cards |
| `--radius-sm` | `10px` | Inputs e botões |
| `--shadow-sm` | `0 1px 2px rgba(15,23,42,.05)` | Cards em repouso |
| `--shadow-md` | `0 14px 34px rgba(15,23,42,.08)` | Cards em destaque / dropdowns |
| `--shadow-lg` | `0 28px 70px rgba(15,23,42,.16)` | Modais / destaques |

**Espaçamento:** escala de 4px — `4, 8, 12, 16, 20, 24, 32, 40, 56`.
**Tipografia:** `Inter`, `Segoe UI`, `system-ui`; fallback local (sem requisições externas).
**Raio:** cards `16px`, inputs/botões `10px`, avatares círculo, ícones em quadrado arredondado `11–12px`.

### Tipografia

| Elemento | Tamanho | Peso |
|---|---|---|
| `h1` | `clamp(1.55rem, 2.6vw, 1.95rem)` | 800 |
| `h2` | `1.15rem` | 750 |
| `h3` | `.98rem` | 700 |
| Texto | `.88–.95rem` | 400 |
| Secundário (`.muted`) | `.87rem` | 400 |
| Rótulo de seção (`.nav-label`, `.page-kicker`) | `.6–.72rem` uppercase | 800 |

---

## 3. Estrutura de telas

### 3.1 Aplicação autenticada — `.app-shell`

```
┌────────────────┬──────────────────────────────────────────────┐
│ Sidebar escura │ Topbar: busca · notificações · usuário         │
│ 264px          ├──────────────────────────────────────────────┤
│ logo           │ Conteúdo (.app-content)                      │
│ organização    │  Page header                                 │
│ navegação      │  Cards, tabelas, formulários                 │
│ usuário / sair │                                              │
└────────────────┴──────────────────────────────────────────────┘
```

- Sidebar fixa (`position: sticky`, `height: 100vh`) com fundo em gradiente escuro.
- Navegação agrupada por rótulos: **Principal**, **Comunicação**, **Marketing**, **Gestão**.
- Item ativo: gradiente `--primary` → `#7C5CFF`, texto branco e sombra.
- Topbar com busca de leads, sino com contador, avatar e menu do usuário.
- Abaixo de **860px** a sidebar vira menu lateral (off-canvas) com botão hambúrguer e overlay.
- Conteúdo com largura máxima de `1480px`, padding `26–28px`.

Layouts:
- `layouts/member` — painel da organização (sidebar completa).
- `layouts/panel` — painel Root (badge laranja “Root Admin”, navegação da plataforma).
- `layouts/app` — páginas públicas simples (formulários, convites, chat).
- `layouts/auth` — login e cadastro.

### 3.2 Autenticação — `.auth-split`

Tela dividida em duas colunas:

- **Esquerda (marca):** fundo escuro com brilhos em gradiente, logo claro, chamada
  “Mais clientes. Mais oportunidades. Mais resultados.”, três benefícios e selo de segurança/LGPD.
- **Direita (ação):** fundo `#F8FAFF`, card branco `470px` com logo, título, formulário,
  divisor “ou” e link de cadastro/login.
- Abaixo de **820px**: apenas a coluna do formulário, com logo compacta no topo.

Login e cadastro usam exatamente esse layout (`auth/login.blade.php`, `auth/register.blade.php`).
Recuperação de senha e verificação em duas etapas também usam o mesmo layout (`auth/forgot`, `auth/reset`, `auth/two-factor`).

---

## 4. Componentes

### Botões (`.btn`)

| Classe | Uso |
|---|---|
| `.btn` | Ação primária (roxo) |
| `.btn.secondary` | Ação secundária (contorno) |
| `.btn.ghost` | Ação discreta |
| `.btn.danger` | Excluir / ação destrutiva |
| `.btn.small` | Ações em tabelas e cabeçalhos |
| `.btn.full` | Largura total |

Altura mínima `42px` (toque confortável), ícone opcional de `16–18px`.
Sempre com texto claro e verbo de ação (“Criar lead”, “Salvar alterações”).

### Cards (`.card`, `.panel-card`)

- `.card` — conteúdo geral (formulários, tabelas, configurações), padding `24px`.
- `.panel-card` — blocos do dashboard, padding `20px`.
- `.card.narrow` — autenticação/estado vazio (`460px`).
- `.card-head` — título + descrição + ação alinhados.

### KPIs (`.kpi-grid`, `.kpi-card`)

Grade de 4 colunas em desktop; 2 em telas médias; 1 no celular.

```
[ícone colorido]                         [↑ 12%]
Rótulo
1.248
1.248 novo(s) no período
```

- Ícones com tons: `blue`, `purple`, `green`, `orange`.
- Variação positiva em verde, negativa em vermelho, “Novo” quando não há base anterior.
- Valores monetários grandes usam formato compacto (`R$ 2,2 mi`) e o detalhe mostra o valor completo.

### Donut (`.donut`)

- Gráfico com `conic-gradient` gerado no servidor; centro com total de leads.
- Legenda com ponto de cor, origem e percentual.
- Cores fixas: `#3B82F6`, `#7C3AED`, `#EC4899`, `#F59E0B`, `#10B981`, `#94A3B8`.

### Lista de leads (`.lead-list`)

- Cada lead é uma linha/card com: avatar de iniciais, nome, e-mail/telefone, **ID permanente** e chips dos campos configurados.
- Chips mostram o rótulo do campo e o valor (`Status`, `Temperatura`, `Segmento`, `Orçamento`).
- Ações `Ver` e `Editar` ficam à direita; em telas pequenas a linha vira bloco vertical.
- O ID longo é truncado visualmente na lista, mas o valor completo aparece no `title` e na tela de detalhe.
- Estado vazio orienta a cadastrar o primeiro lead.

### Detalhe do lead (`.lead-detail-grid`, `.detail-list`, `.timeline`)

- Dois cards lado a lado: **Dados do lead** (campos fixos) e **Campos do CRM** (personalizados).
- Campos desativados continuam visíveis quando têm valor, com badge `desativado` — preservando o histórico.
- O bloco **Histórico do lead** usa `.timeline` com ícone, título amigável, autor e data.
- O ID do lead (`LD-...`) é exibido no cabeçalho e no detalhe, sempre em fonte monoespaçada.

### Gestão de CRM (`.guideline-list`, `.field-admin`)

- Três cartões explicam: ID do lead fixo, campos não são excluídos e ID do campo é estável.
- Formulário de criação: nome, tipo, opções (para seleção), obrigatório e “mostrar na lista”.
- Cada campo configurado mostra `FLD-...`, tipo, quantidade de leads com dados, status e ações.
- A edição fica em um `<details>` para manter a página limpa.
- Ao alterar o tipo de um campo com dados, exibe alerta e exige confirmação explícita.
- Nunca há botão de excluir: apenas **Desativar** e **Reativar**.

### Módulos (`.module-grid`, `.start-grid`)

- `.module-grid` lista os módulos ativos e desativados na página de configurações.
- `.start-grid` apresenta os primeiros passos no dashboard quando ainda não há leads.
- O controle técnico fica em `config/modules.php` (`QEAI_MODULES` no `.env`).

### Alertas (`.alert`)

`.alert.ok`, `.alert.err`, `.alert.warn`. Sempre no topo do conteúdo, com texto direto.
Alertas de sucesso desaparecem automaticamente após 7 segundos.

### Formulários

- `label` em coluna, texto `0.81rem`, campo com borda `#D9E0EC` e raio `10px`.
- Grid de 2 colunas: `.grid-2` (`.full` ocupa a linha inteira).
- Checkbox: `.check` (linha, com `accent-color` da marca).
- Erros: `.alert.err` no topo e/ou `.field-error`.
- Login/cadastro: `.auth-input` com ícone à esquerda e botão de mostrar/ocultar senha.

### Tabelas (`.table`)

- Cabeçalho em maiúsculas pequenas (`0.66rem`), divisores suaves, hover de linha.
- Ações à direita com `.row-actions` e `.btn.small`.
- Em telas pequenas: rolagem horizontal (`display:block; overflow-x:auto`).
- Paginação: `.pagination`, `.pagination-link`, `.pagination-status`.

### Navegação e usuário

- `.side-link` — item de navegação com ícone, estado ativo em gradiente.
- `.side-user` — cartão do usuário no rodapé da sidebar.
- `.user-dropdown` — `<details>` com avatar, nome, organização e menu (configurações, trocar organização, sair).
- `.icon-btn` — botão circular com ícone (sino, ajuda).
- `.avatar` — iniciais do usuário/organização em gradiente.

### Ícones

```blade
<x-icon name="users" :size="18" />
```

Nomes disponíveis: `home`, `users`, `user-plus`, `briefcase`, `building`, `check-square`,
`calendar`, `message`, `inbox`, `bar-chart`, `chart-pie`, `settings`, `bell`, `search`,
`help`, `chevron-down`, `chevron-right`, `plus`, `arrow-right`, `mail`, `lock`, `eye`,
`eye-off`, `target`, `trending-up`, `dollar`, `handshake`, `phone`, `zap`, `grid`, `filter`,
`menu`, `x`, `shield`, `key`, `code`, `megaphone`, `share`, `link`, `trash`, `pencil`,
`download`, `clock`, `activity`, `logout`, `sparkles`, `list-check`, `circle`.

Regras: usar `currentColor`, tamanho `14–22px` e sempre `aria-hidden` (o componente já aplica).

---

## 5. Mapa de navegação da organização

| Menu | Rota | Permissão |
|---|---|---|
| Dashboard | `/app/organizacoes/{org}/dashboard` | `org.leads` |
| Leads | `/crm/contatos` | `org.leads` |
| Gestão de CRM | `/crm/campos` | `org.crm_fields` |
| Configurações | `/` da organização | `org.settings` / `org.members` |
| Logs | `/logs` | `org.logs` |

Itens sem permissão **não são exibidos**. O Root vê a estrutura, mas dados de negócio
continuam bloqueados sem vínculo com a organização (tela de governança).

### Módulos desativados nesta etapa

Os módulos abaixo continuam no código e com os dados preservados, mas **não aparecem**
na navegação enquanto não forem reativados em `QEAI_MODULES`:

`companies`, `pipeline`, `tasks`, `inbox`, `channels`, `forms`, `social`, `ads`, `integrations`.

O núcleo ativo é: `dashboard`, `leads`, `crm_fields`, `settings`, `logs`.
O middleware `module` bloqueia (404) o acesso direto às telas de módulos desativados e também
aos endpoints públicos correspondentes (formulários, chat, webhooks e WhatsApp).
A API de leads permanece ativa enquanto o módulo `leads` estiver habilitado.

---

## 6. Responsividade

| Breakpoint | Comportamento |
|---|---|
| `> 1080px` | KPIs em 4 colunas; gráficos em 2 colunas; sidebar fixa |
| `≤ 1080px` | KPIs em 2 colunas; gráficos empilhados |
| `≤ 860px` | Sidebar off-canvas com hambúrguer e overlay; topbar compacta |
| `≤ 820px` | Formulários em 1 coluna; tabelas com rolagem; login sem painel esquerdo |
| `≤ 620px` | Busca oculta; KPIs em 1 coluna; avatar sem nome no topbar |
| `≤ 420px` | Ajustes de padding e tipografia |

Teste sempre: 360px (celular), 768px (tablet), 1024px, 1366px e 1920px.

---

## 7. Acessibilidade

- Contraste mínimo AA para texto e botões.
- Foco visível com anel de `3px` usando a cor primária.
- Botões apenas com ícone recebem `aria-label` e `title`.
- Formulários com `label for` ligado ao `id` do campo.
- Menu off-canvas fecha com `Esc`, clique no overlay ou no botão “X”.
- Não usar cor como único indicador de status (sempre acompanhar de texto).
- Respeitar `prefers-reduced-motion` em novas animações (quando adicionadas).

---

## 8. White label

- Nome, razão social, CNPJ e e-mail de contato: `PlatformSettingsController`.
- Cor primária: `--primary`, aplicada no `<head>` de todos os layouts.
- Logo: `public/img/logo.svg` (fundo claro) e `public/img/logo-light.svg` (fundo escuro).
- Ao trocar a marca, manter as duas versões e a proporção `152×44` (ou múltiplo).

---

## 9. Boas práticas e o que evitar

**Faça**
- Use os tokens de cor; nunca valores soltos (`#6D3DF5` apenas no token).
- Prefira textos curtos, em pt-BR, com verbos de ação.
- Coloque uma ação primária por tela/bloco.
- Exiba estados vazios com orientação do próximo passo.
- Use `confirm()` para ações destrutivas e `@method('DELETE')` nos formulários.
- Mantenha os formulários com `maxlength`, `autocomplete` e validação no servidor.

**Evite**
- Cards aninhados em excesso ou sombras fortes.
- Mais de 3 níveis de navegação.
- Tabelas sem ação clara ou sem estado vazio.
- Ícones sem rótulo em ações importantes.
- Cores de status fora dos tokens (`--ok`, `--warn`, `--err`).

---

## 10. Checklist de revisão de UI

- [ ] A tela funciona em 360px, 768px e 1366px.
- [ ] Sidebar e topbar mantêm o item ativo correto.
- [ ] A ação principal está visível sem rolar a tela.
- [ ] Formulários exibem erros e sucesso.
- [ ] Tabelas têm cabeçalho legível e ações à direita.
- [ ] Estados vazios orientam o próximo passo.
- [ ] Nenhuma cor hardcoded fora dos tokens.
- [ ] Foco de teclado visível em todos os controles.
- [ ] Textos em pt-BR e sem jargão desnecessário.
