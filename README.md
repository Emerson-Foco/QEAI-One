# Q One

CRM e gestão de leads para empresas, com campos personalizáveis, histórico por
lead e visões de Tabela, Kanban, Calendário e Cronograma.

## Stack

- PHP 8.2+ / Laravel 12
- MySQL 8 / MariaDB
- Blade + CSS próprio (sem framework de UI)
- Sessões, cache e filas no banco (ou `sync` na hospedagem compartilhada)

## Requisitos

- PHP 8.2+ com `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`,
  `ctype`, `json`, `fileinfo` e `curl`
- Composer 2
- MySQL/MariaDB
- HTTPS em produção

## Ambiente local

```bash
git clone git@github.com:Emerson-Foco/QEAI-One.git qeai-one
cd qeai-one
cp .env.example .env
composer install
php artisan key:generate
```

Crie o banco `qeai_one` e ajuste `DB_*` no `.env`. Depois:

```bash
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

Abra `http://127.0.0.1:8000/install` na primeira execução para criar o ADM Root.
O instalador grava `storage/app/installed.json` e passa a retornar 404.

## Módulos

Os módulos ativos são definidos em `config/modules.php` e podem ser ajustados
por `QEAI_MODULES`. O escopo atual habilitado é:

```env
QEAI_MODULES=dashboard,leads,crm_fields,settings,logs
```

Módulos fora do escopo continuam no código, mas ficam bloqueados pelo middleware
`module`, inclusive por acesso direto à URL.

## Testes e qualidade

```bash
php artisan test
vendor/bin/pint --test
php artisan view:cache
php artisan view:clear
```

## Deploy

O guia completo de produção, Git/SSH, cron, SMTP, segurança e rollback está em
[`docs/DEPLOY-GIT.md`](docs/DEPLOY-GIT.md). Particularidades do hPanel estão em
[`docs/DEPLOY-HOSTINGER.md`](docs/DEPLOY-HOSTINGER.md).

## Documentação

- [`docs/DESIGN-SYSTEM.md`](docs/DESIGN-SYSTEM.md) — padrão visual e componentes.
- [`docs/SECURITY.md`](docs/SECURITY.md) — controles e checklist de segurança.
- [`docs/CHECKLIST.md`](docs/CHECKLIST.md) — estado do produto e roadmap.
- [`AGENTS.md`](AGENTS.md) — regras para desenvolvimento e agentes.
