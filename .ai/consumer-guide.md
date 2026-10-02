# Guia do vagkaefer/cms-filament para o projeto consumidor

> Este arquivo vive em `vendor/vagkaefer/cms-filament/.ai/consumer-guide.md` e é
> carregado no `CLAUDE.md` do projeto via `@import`. Ele é mantido pelo package e
> se atualiza a cada `composer update`. **Não edite este arquivo** — mudanças em
> `vendor/` são perdidas; regras do projeto vão no `CLAUDE.md` do próprio projeto.

## Contexto

O **cms-filament** é um package Laravel/Filament 5 que acrescenta ao painel já
existente do projeto: usuários, cargos/permissões (Spatie), auditoria, backups,
configurações e MFA nativo do Filament.

- **Nunca edite arquivos dentro de `vendor/vagkaefer/cms-filament/`.** Para
  customizar, use os pontos de extensão: configs publicadas, toggles do plugin e
  Resources próprios no `app/`.
- Faça "the Laravel way": gere arquivos com `php artisan make:`, declare casts em
  `casts()`, use named routes.
- Convenções de PHP: sempre curly braces, constructor property promotion, return
  types e type hints explícitos, PHPDoc em vez de comentários inline.

## Como o package se integra

O package **não tem painel próprio**. Ele entra como plugin no painel do projeto:

```php
->plugin(\VagKaefer\CmsFilament\CmsFilamentPlugin::make())
```

O model de usuário é o do projeto (`config('auth.providers.users.model')`) e usa
a trait `VagKaefer\CmsFilament\Models\Concerns\CmsUser`, declarando os contratos
`FilamentUser`, `HasAppAuthentication`, `HasAppAuthenticationRecovery`,
`HasEmailAuthentication`, `HasPasskeys` (spatie/laravel-passkeys) e `Auditable`.

## Login por passkey

São dois caminhos que convivem: e-mail + senha + segundo fator, ou passkey. Quem
entra com passkey não digita o código do 2FA, porque a passkey já é posse do
aparelho + biometria ou PIN. A gestão fica no Perfil, abaixo do segundo fator.

- **Liga sozinho** quando o User declara `HasPasskeys`. Sem o contrato, o plugin
  não mostra o botão nem a seção no perfil. Para desligar com o contrato
  declarado, use `->passkeys(false)`.
- **Domínio:** `PASSKEYS_RP_ID` no `.env`, e vazio usa o host do `APP_URL`. Use o
  domínio registrável (`exemplo.com.br`): uma passkey criada nele vale para
  `www.` e para outros subdomínios. Uma criada em `novo.exemplo.com.br` morre
  quando o site muda de endereço.
- **HTTPS obrigatório**, exceto em `localhost`, que o package libera para
  desenvolvimento.
- **Perfil próprio:** se o painel declara `->profile(MinhaPagina::class)`, o
  plugin não troca. Acrescente `Livewire::make(\VagKaefer\CmsFilament\Livewire\PasskeysManager::class)`
  no `content()` da página.
- **Asset JS:** o `@simplewebauthn/browser` vai pronto no package e é publicado
  em `public/js/vagkaefer/cms-filament/` pelo `php artisan filament:assets`
  (que o `filament:upgrade` do composer já roda).
- O login por passkey respeita `canAccessPanel()`: conta inativa não entra.

## Autorização de Resources do projeto

Use `VagKaefer\CmsFilament\Filament\Traits\HasResourcePermissions` nos Resources
que precisam de controle de acesso.

- O prefixo vem do nome da classe:
  `Str::snake(Str::beforeLast(class_basename($resource), 'Resource'))`.
  `NewsResource` → `view_news`, `create_news`, `edit_news`, `delete_news`.
- **Bypass do administrador:** quem tem o cargo de `cms-filament.admin_role`
  (padrão `admin`) passa em todas as checagens. Não crie permissões para ele.
- Depois de criar Resources, rode `php artisan permissions:generate-resources`.
- Métodos `can*()` declarados na própria classe do Resource vencem os da trait —
  é assim que se fixa uma regra de negócio (por exemplo, uma caixa de entrada
  onde ninguém cria registros).

## Instalação e atualização

```bash
php artisan vendor:publish --tag=cms-filament-config   # opcional
php artisan migrate
php artisan db:seed --class="VagKaefer\\CmsFilament\\Database\\Seeders\\PermissionsSeeder"
```

| Tag | Publica | Destino |
| --- | --- | --- |
| `cms-filament-config` | `cms-filament`, `permission`, `audit`, `backup` | `config/` |
| `cms-filament-migrations` | migrations do package | `database/migrations` |

Os administradores vêm de `CMS_ADMIN_EMAILS` (lista separada por vírgula) — o
seeder não tem e-mail embutido.

**Atualização:** `composer update vagkaefer/cms-filament`. Migrations novas são
carregadas automaticamente; rode `php artisan migrate`.

## Armadilhas conhecidas

- A flag `active` no usuário controla o acesso: conta inativa não loga no painel.
- O cargo administrativo não pode ser excluído nem renomeado (trava no model,
  vale também para tinker e seeders).
- Chaves listadas em `cms-filament.configurations.encrypted_keys` nunca são
  exibidas na tela de Configurações: salvar o campo em branco mantém o valor.
- Backup com dump de banco exige o binário do cliente no servidor.
- Contas de e-mail em `@cloudger.com.br`, `@cloudger.host` e `@kaefer.eng.br` são
  as do fornecedor: só aparecem e só podem ser administradas entre si. Para
  qualquer outro usuário — inclusive quem tem o cargo administrativo — elas não
  existem na tela de Usuários, na busca global, no export nem na auditoria.
