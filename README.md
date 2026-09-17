# CMS Filament

Painel administrativo pronto para projetos Laravel + [Filament 5](https://filamentphp.com):
usuários, cargos e permissões, auditoria, backups, configurações e autenticação
em dois fatores.

O package **não cria um painel próprio** — ele se instala como plugin no painel
que o projeto já tem, preservando id, rota, cores e customizações.

[![PHPUnit](https://github.com/vagkaefer/cms-filament/actions/workflows/phpunit.yml/badge.svg)](https://github.com/vagkaefer/cms-filament/actions/workflows/phpunit.yml)
[![PHPStan](https://github.com/vagkaefer/cms-filament/actions/workflows/phpstan.yml/badge.svg)](https://github.com/vagkaefer/cms-filament/actions/workflows/phpstan.yml)

## O que vem junto

| Tela | O que faz |
| --- | --- |
| Usuários | CRUD, cargos, ativar/desativar conta, personificar (impersonate) |
| Cargos | CRUD de papéis; o cargo administrativo é protegido contra exclusão e renomeação |
| Permissões | CRUD e geração automática a partir dos Resources |
| Auditoria | Histórico de alterações (owen-it/laravel-auditing) |
| Configurações | Pares chave/valor editáveis, com criptografia para segredos |
| Backups | Interface do spatie/laravel-backup |
| Logs | Visualizador de logs da aplicação |

Mais: bypass de autorização para o cargo administrativo, MFA nativo do Filament
(app autenticador e código por e-mail), indicador de ambiente e encerramento das
demais sessões quando a senha muda.

## Requisitos

- PHP 8.4+
- Laravel 12 ou 13
- Filament 5
- Um painel Filament já configurado no projeto

## Instalação

```bash
composer require vagkaefer/cms-filament
```

Durante o desenvolvimento, via repositório local:

```json
{
    "repositories": [
        { "type": "path", "url": "../cms-filament", "options": { "symlink": true } }
    ]
}
```

```bash
composer require vagkaefer/cms-filament:@dev
```

### 1. Registre o plugin no painel

```php
use VagKaefer\CmsFilament\CmsFilamentPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('painel')
        // ...
        ->plugin(CmsFilamentPlugin::make());
}
```

### 2. Prepare o model de usuário

```php
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Foundation\Auth\User as Authenticatable;
use OwenIt\Auditing\Contracts\Auditable;
use VagKaefer\CmsFilament\Models\Concerns\CmsUser;

class User extends Authenticatable implements Auditable, FilamentUser,
    HasAppAuthentication, HasAppAuthenticationRecovery, HasEmailAuthentication
{
    use CmsUser;
}
```

A trait acrescenta cargos, auditoria, os métodos de MFA e a flag `active`, que
controla o acesso ao painel. Não é preciso mexer em `casts()` nem em `$fillable`.

### 3. Rode as migrations

```bash
php artisan migrate
```

São criadas as tabelas de permissões, auditoria e configurações, e acrescentadas
ao `users` as colunas `active` e as três do segundo fator. A migration é
idempotente: colunas que já existirem são mantidas.

### 4. Defina o administrador e gere as permissões

```dotenv
CMS_ADMIN_EMAILS=voce@exemplo.com,colega@exemplo.com
```

```bash
php artisan db:seed --class="VagKaefer\\CmsFilament\\Database\\Seeders\\PermissionsSeeder"
```

O seeder cria o cargo `admin`, gera as permissões de todos os Resources e
atribui o cargo aos e-mails configurados. Pode rodar quantas vezes quiser.

### 5. (Opcional) Publique as configurações

```bash
php artisan vendor:publish --tag=cms-filament-config
```

Publica `cms-filament.php`, `permission.php`, `audit.php` e `backup.php`. Sem
publicar nada o package já funciona com os padrões.

## Autorização dos seus Resources

Use a trait nos Resources do projeto para que respeitem as permissões:

```php
use VagKaefer\CmsFilament\Filament\Traits\HasResourcePermissions;

class NewsResource extends Resource
{
    use HasResourcePermissions;
}
```

O prefixo vem do nome da classe: `NewsResource` gera `view_news`, `create_news`,
`edit_news` e `delete_news`. Depois de criar um Resource, rode:

```bash
php artisan permissions:generate-resources
```

Quem tem o cargo administrativo passa em todas as checagens, então não é preciso
conceder permissão a ele.

## Ajustando o plugin

```php
CmsFilamentPlugin::make()
    ->navigationGroup('Sistema')      // grupo do menu (padrão: Administração)
    ->backups(false)                  // desliga a tela de backups
    ->logViewer(false)                // desliga o visualizador de logs
    ->environmentIndicator(false)     // desliga o selo de ambiente
    ->multiFactorAuthentication(false) // desliga o segundo fator
    ->versionWidget()                 // widget de versão no dashboard
    ->footer()                        // rodapé com a versão
    ->userExport()                    // exportação de usuários (veja abaixo)
```

A exportação de usuários é opcional porque depende das migrations de
`filament-actions`, da tabela de notificações e de um worker de fila ativo.

## Comandos

| Comando | O que faz |
| --- | --- |
| `permissions:generate-resources` | Cria as permissões dos Resources que usam a trait |
| `logs:clean` | Remove logs antigos |
| `log:clean` | Trunca o log quando passa do tamanho máximo |
| `cms-filament:ai-setup` | Registra o guia de IA no `CLAUDE.md` do projeto |

## Backups em SQLite e hospedagem compartilhada

O dump do banco depende do binário do cliente (`sqlite3`, `mysqldump`,
`pg_dump`) existir no servidor. Onde ele não existe, publique `backup.php` e
esvazie `source.databases`: no SQLite o arquivo do banco já entra no backup de
arquivos.

## Desenvolvimento

```bash
composer ci        # pint, phpcs, phpmd, phpstan e phpunit
./ci-local.sh      # o mesmo, sem parar no primeiro erro
```

## Licença

MIT. Veja [LICENSE](LICENSE).
