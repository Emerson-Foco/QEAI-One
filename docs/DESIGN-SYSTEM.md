# QEAI One — Design System

Padrão visual da plataforma. Objetivo: consistência, legibilidade e aparência profissional,
mantendo **HTML/CSS/JS puro** (sem build/Node).

Arquivo de estilos: `public/css/app.css`. Layout base: `resources/views/layouts/app.blade.php`
(com variações `layouts/panel.blade.php` e `layouts/member.blade.php`).

---

## 1. Princípios

1. **Clareza primeiro** — hierarquia visual simples, muito espaço em branco, foco no conteúdo.
2. **Consistência** — sempre reutilizar tokens e componentes; não criar variações soltas.
3. **Acessível** — contraste adequado, foco visível, navegação por teclado, `aria-*` quando aplicável.
4. **Sem dependências** — CSS próprio; nenhuma biblioteca de UI externa.

## 2. Tokens (variáveis CSS)

Definidos em `:root`. A **cor primária pode ser trocada por organização/plataforma** (white label)
via `Setting::primary_color`, injetada no `<head>` como `--primary`.

| Token | Valor padrão | Uso |
|---|---|---|
| `--primary` | `#6D3DF5` | Ações principais, links, destaques |
| `--secondary` | `#18C7D9` | Apoio (gráficos, ícones secundários) |
| `--accent` | `#FF7048` | Alertas de destaque / ênfase |
| `--dark` | `#0F1120` | Textos fortes, barras escuras |
| `--ink` | `#22243A` | Texto padrão |
| `--muted` | `#6B6C80` | Texto secundário |
| `--border` | `#E7E5EF` | Bordas e divisores |
| `--surface` | `#FFFFFF` | Fundo de cards |
| `--soft` | `#F7F6FB` | Fundo da página / seções |
| `--ok` / `--ok-bg` | `#1F7A4D` / `#EAF7EF` | Sucesso |
| `--err` / `--err-bg` | `#A32A22` / `#FDEEEC` | Erro |
| `--radius` | `14px` | Cantos de cards |
| `--radius-sm` | `10px` | Inputs e botões |
| `--shadow-sm` | `0 1px 2px rgba(16,12,40,.06)` | Cards em repouso |
| `--shadow-md` | `0 12px 32px rgba(16,12,40,.10)` | Cards em destaque |
| `--shadow-lg` | `0 24px 60px rgba(16,12,40,.16)` | Modais / hero |

Espaçamento: escala de 4px (`4, 8, 12, 16, 24, 32, 48, 64`). Tipografia: pilha de fontes do sistema
(`Inter, system-ui, …`).

## 3. Tipografia

| Elemento | Tamanho | Peso |
|---|---|---|
| `h1` | `1.9rem` (com `clamp`) | 700 |
| `h2` | `1.25rem` | 650 |
| `h3` | `1rem` | 650 |
| Texto | `0.92rem` | 400 |
| Secundário (`.muted`) | `0.85rem` | 400 |
| `.eyebrow` | `0.7rem` uppercase, `letter-spacing .12em` | 700 |

## 4. Componentes

### Botões (`.btn`)
- Base: `.btn` (primário, fundo `--primary`).
- `.btn.secondary` — contorno, fundo branco.
- `.btn.danger` — contorno vermelho (ações destrutivas).
- `.btn.small` — tamanho reduzido (tabelas/ações em linha).
- `.btn.full` — largura total (formulários de login/cadastro).
- Estados: `:hover`, `:focus-visible` (anel de foco), `:disabled` (opacidade).

### Cards (`.card`)
Bloco branco, borda `--border`, raio `--radius`, sombra suave. Variações `.card.narrow` (autenticação)
e `.card.wide` (instalador). Cada card tem um `h2` no topo.

### Formulários
- `label` em coluna com `input`/`select`/`textarea` (classe automática).
- Grade de 2 colunas: `.grid-2` (`.full` ocupa a linha inteira).
- Checkbox: `.check` (linha; usado para opções e consentimento).
- Erros: `.alert.err` no topo do formulário; `.error`/`field-error` junto ao campo.

### Alertas (`.alert`)
`.alert.ok` (sucesso) e `.alert.err` (erro). Sempre no topo do conteúdo.

### Tabelas (`.table`)
Cabeçalho em maiúsculas pequenas; linhas com divisor; ações à direita (`.right`, `.row-actions.inline`).
Badges (`.badge`) para status.

### Navegação
- `.topbar` — barra superior (marca, sino, usuário, sair).
- `.panel-nav` — abas da seção atual (`.active`).
- `.bell` — sino com contador de notificações (`.badge`).
- `.pill` / `.eyebrow` — rotulagem de contexto.

### Outros
- Paginação: `.pagination`, `.pagination-link`, `.pagination-status`.
- Kanban: `.kanban`, `.kanban-col`, `.deal-card`, `.deal-value`, `.deal-meta`.
- Chat: `.chat-log`, `.chat-msg.in/.out`, `.chat-bubble`.
- Onboarding: `.onboarding-list` (`li.done`).
- Estado vazio: `.empty-state`.
- Selo de segurança de e-mail: `.verify-banner`.
- Honeypot (formulários públicos): `.hp` (invisível para humanos).

## 5. Layouts

- **Público** (`layouts/app`): landing, login, cadastro, formulários públicos, chat, convites.
- **Painel da plataforma** (`layouts/panel`): visão do ADM Root (`/painel`).
- **Painel da organização** (`layouts/member`): organização ativa (`/app/organizacoes/{org}`), com
  seletor de organização quando o usuário participa de mais de uma.

## 6. Responsividade

- Breakpoints: `820px` (formulários/tabelas), `720px`/`620px` (nav e cards).
- Tabelas viram scroll horizontal em telas pequenas (`.table { display:block; overflow-x:auto }`).
- Grades de 2 colunas colapsam para 1.

## 7. White label

Nome, logo (previsto) e **cor primária** são configuráveis pelo ADM Root em **Configurações**.
A cor primária é aplicada em `:root{--primary}` no layout, refletindo em botões, links e destaques.

## 8. Boas práticas

- Não hardcodar cores; usar tokens.
- Ações destrutivas pedem `confirm()` e usam `.btn.danger`/`.secondary`.
- Sempre exibir `session('status')` e `$errors` (já tratado nos layouts).
- Textos em pt-BR, curtos e diretos.
