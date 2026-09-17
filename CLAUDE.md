# CMS Filament (vagkaefer/cms-filament) — instruções do projeto

## O que é este projeto

**Package Laravel/Filament** (`type: library`), instalado em projetos Laravel
consumidores via Composer. **Não é uma aplicação** — não tem app HTTP própria,
banco próprio nem frontend toolchain. A UI vem do **Filament 5** e seus plugins.

- Namespace PSR-4: `VagKaefer\CmsFilament\` → `src/`.
- **Não define painel próprio.** O ponto de entrada é
  `src/CmsFilamentPlugin.php`, um plugin Filament que o projeto consumidor
  acrescenta ao painel dele. Nada de `->default()`, id ou path fixos.
- **Não entrega model de usuário.** O model é o do projeto
  (`config('auth.providers.users.model')`, via `cms_filament_user_model()`); o
  package oferece a trait `Models\Concerns\CmsUser`.
- Provider auto-descoberto: `Providers\CmsFilamentServiceProvider` (merge de
  configs, migrations, views, publish, `Gate::before`, observer, comandos).

### NEVER do

- **NUNCA commite por iniciativa própria** — só após autorização explícita.
- **Não invente comandos** que não existem no repo. Os válidos estão em
  `composer.json` → `scripts`.
- Não altere dependências (`require`/`require-dev`) sem aprovação.
- Não crie READMEs/docs a menos que solicitado.
- Não adicione model de usuário nem painel ao package: as duas coisas são do
  projeto consumidor.

## Stack

- PHP `^8.4`, Laravel 12/13, Filament `^5` (que exige Livewire `^4`).
- Spatie Laravel Permission `^8`, owen-it/laravel-auditing `^14`,
  tapp/filament-auditing, shuvroroy/filament-spatie-laravel-backup `^4`,
  stechstudio/filament-impersonate `^5`, achyutn/filament-log-viewer,
  pxlrbt/filament-environment-indicator.
- **MFA é o nativo do Filament 5** (`AppAuthentication`, `EmailAuthentication`).
  Não há package de 2FA de terceiros nem passkeys.
- **Sem Docker.** `composer`, `phpunit` e `./ci-local.sh` rodam direto no host.
- `require-dev`: orchestra/testbench `^11`, phpunit, pint, larastan, phpmd, phpcs.

## Estrutura de `src/`

- `CmsFilamentPlugin.php` — plugin Filament; registra resources, sub-plugins e MFA.
- `Providers/CmsFilamentServiceProvider.php` — boot/register do package.
- `Models/` — Role, Permission, Configuration, Audit (+ `Concerns/CmsUser`).
- `Filament/` — `Resources/`, `Traits/`, `Widgets/`, `Exports/`.
- `Database/Migrations/` e `Database/Seeders/PermissionsSeeder`.
- `Config/` — `cms-filament.php` (principal), `permission.php`, `audit.php`, `backup.php`.
- `helpers.php` — `app_version()`, `cms_filament_version()`,
  `cms_filament_user_model()`, `cms_filament_is_admin()`.
- Tags de publish: `cms-filament-config`, `cms-filament-migrations`.

## Regras de desenvolvimento

### PHP

- Sempre curly braces em estruturas de controle, mesmo de uma linha.
- Constructor property promotion.
- Return types e type hints explícitos em todos os métodos/parâmetros.
- PHPDoc (com array shapes) em vez de comentários inline, exceto lógica
  excepcionalmente complexa. Comentário explica **por quê**, não o quê.
- Siga as convenções dos arquivos irmãos ao criar/editar. Textos de UI em pt-BR.

### Decisões estruturais a preservar

- **Chaves das tabelas do package são bigint.** As colunas que referenciam o
  usuário (`model_has_roles.model_id`, `model_has_permissions.model_id`,
  `audits.user_id`) são tipadas em migrate time pelo `getKeyType()` do model do
  projeto; `audits.auditable_id` é `string(36)` para caber id inteiro e UUID.
- **`Gate::before` é registrado via `callAfterResolving(Gate::class)`.** Resolver
  o Gate direto no boot o instancia antes do provider do Spatie, que também
  espera o Gate para plugar a checagem — e `can()` passaria a devolver sempre
  false. Há teste cobrindo (`HasResourcePermissionsTest`).
- **`permission.php` e `audit.php` são fundidos no `register()`.** Os providers
  de origem só fundem no boot, tarde demais para uma migration que roda antes.
- **Coerência do prefixo de permissão:** o que `permissions:generate-resources`
  grava precisa bater com o que `HasResourcePermissions` consulta em runtime.
  Divergência = permissões criadas que a autorização nunca lê.

### Testes (OBRIGATÓRIO)

- Toda correção/feature precisa de teste. Escreva/atualize e rode.
- Orchestra Testbench + SQLite in-memory. `tests/TestCase.php` registra
  explicitamente os providers (o Testbench não faz auto-discovery), roda as
  migrations padrão do Laravel antes das do package e aponta
  `auth.providers.users.model` para `tests/Fixtures/User.php`.
- A fixture declara `$fillable` explícito: a trait faz `mergeFillable`, e um
  model com `$guarded = []` passaria a aceitar só o que a trait acrescenta.

## CI/CD & Qualidade

| Comando | O que faz |
| --- | --- |
| `composer test` | PHPUnit |
| `composer lint` / `lint:test` | Pint (corrige / verifica) |
| `composer stan` | Larastan (level 5) |
| `composer md` | PHPMD |
| `composer cs` | PHPCS (PSR-12) |
| `composer ci` | Tudo em sequência |

- **Antes de finalizar mudanças em PHP:** rode `./ci-local.sh` — espelha o CI,
  não para no primeiro erro e imprime um resumo. `--fix` aplica correções de
  estilo.
- **GitHub Actions:** 5 workflows (`pint`, `phpcs`, `phpms`, `phpstan`,
  `phpunit`). PHP 8.4, com matriz 8.4/8.5 nos testes.

## Git / Commits / Release

GitFlow: `main` = produção, `develop` = integração. Releases via `release/x.y.z`,
tag `vX.Y.Z` e merge-back em `develop`. SemVer; detalhes em `VERSIONING.md`.

- Prefixos: `feat:`, `fix:`, `refactor:`, `build:`, `docs:`, `test:`.
- **Nunca commite sem autorização explícita.**
- **Nunca adicione Co-Authored-By** nem se adicione como colaborador.
- Staje só os arquivos que você alterou — nunca `git add -A`/`.`/`commit -a`.
