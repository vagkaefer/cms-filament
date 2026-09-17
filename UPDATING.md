# Atualizando o cms-filament

```bash
composer update vagkaefer/cms-filament
php artisan migrate
```

As migrations novas são carregadas automaticamente pelo provider — não é preciso
republicar nada para obtê-las.

## Quando republicar as configs

Só se você publicou as configs e quer trazer as mudanças do package:

```bash
php artisan vendor:publish --tag=cms-filament-config --force
```

Isso **sobrescreve** suas customizações. Compare antes com `git diff`.

## Permissões novas

Versões que acrescentam telas também acrescentam permissões. Depois de atualizar:

```bash
php artisan db:seed --class="VagKaefer\\CmsFilament\\Database\\Seeders\\PermissionsSeeder"
```

O seeder é idempotente: cria só o que falta e reatribui o cargo administrativo
aos e-mails de `CMS_ADMIN_EMAILS`.

## Guia de IA

O guia em `.ai/consumer-guide.md` é lido do `vendor/` via `@import`, então se
atualiza sozinho. Para garantir a linha de import no `CLAUDE.md`:

```bash
php artisan cms-filament:ai-setup
```
