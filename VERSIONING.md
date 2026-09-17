# Guia de Versionamento e Atualização

Este documento explica como versionar o `vagkaefer/cms-filament` e como os
projetos consumidores atualizam sem quebrar nada.

## Estratégia de Versionamento

Usamos **[Semantic Versioning](https://semver.org/)**: `MAJOR.MINOR.PATCH`.

A versão é publicada exclusivamente como **tag Git** (`vX.Y.Z`). O
`composer.json` **não** declara o campo `version`: o Composer deriva a versão da
tag, e `cms_filament_version()` lê o que está de fato instalado via
`Composer\InstalledVersions`. Declarar a versão no `composer.json` criaria uma
segunda fonte de verdade fadada a desincronizar.

### MAJOR (v2.0.0)

**Breaking changes** — requer alterações nos projetos consumidores.

**Exemplos:**

- Remover ou renomear métodos públicos do `CmsFilamentPlugin`.
- Alterar estrutura de tabelas de forma incompatível (migrations).
- Mudar assinatura de métodos das traits públicas (`CmsUser`,
  `HasResourcePermissions`).
- Remover chaves obrigatórias dos configs publicados.
- Alterar namespaces.

**Atualização nos projetos:**

```bash
# Requer atenção manual
composer require vagkaefer/cms-filament:^2.0
# Seguir o guia de migração das release notes
```

### MINOR (v0.2.0, v1.1.0)

**Novas funcionalidades** — compatíveis com versões anteriores.

**Exemplos:**

- Novos resources, widgets ou exports Filament.
- Novos métodos de configuração no plugin (sem alterar os existentes).
- Novas chaves de config com valor padrão.
- Novos comandos Artisan.
- Melhorias de performance.

**Atualização nos projetos:**

```bash
composer update vagkaefer/cms-filament
```

### PATCH (v0.1.1)

**Bug fixes.**

**Exemplos:**

- Corrigir validações ou políticas de autorização.
- Corrigir migrations.
- Corrigir geração de permissões (`permissions:generate-resources`).

**Atualização nos projetos:**

```bash
composer update vagkaefer/cms-filament
```

> **Nota sobre a série 0.x:** enquanto a versão MAJOR for `0`, o SemVer trata
> cada MINOR como potencialmente breaking. O constraint `^0.1` aceita apenas
> `0.1.x`. Projetos consumidores devem fixar `^0.1` até a v1.0.0.

## Workflow de Release (GitFlow)

`main` = produção, `develop` = integração. Releases saem de uma branch
`release/x.y.z`.

### 1. Desenvolvimento

```bash
git checkout develop
git checkout -b feature/novo-recurso
# desenvolver
git commit -m "feat: adiciona novo recurso X"
git push origin feature/novo-recurso
```

Após review, merge em `develop`.

### 2. Abrir a branch de release

```bash
git checkout develop
git checkout -b release/0.2.0
```

### 3. Preparar a release

- Atualizar `CHANGELOG.md`: trocar o cabeçalho da versão por
  `## [0.2.0] - AAAA-MM-DD`.
- Atualizar `README.md` e a matriz de compatibilidade, se a stack mudou.
- Rodar a suíte completa:

```bash
./ci-local.sh
```

### 4. Fechar a release

```bash
# Merge em main
git checkout main
git merge --no-ff release/0.2.0

# Tag na main
git tag v0.2.0 -m "Release v0.2.0"
git push origin main v0.2.0

# Merge-back em develop
git checkout develop
git merge --no-ff release/0.2.0
git push origin develop

# Remover a branch de release
git branch -d release/0.2.0
```

### 5. Publicar as release notes

```bash
gh release create v0.2.0 \
  --title "v0.2.0" \
  --notes "$(sed -n '/## \[0.2.0\]/,/## \[/p' CHANGELOG.md | sed '$ d')"
```

## Atualização de Projetos Consumidores

### Cenário 1: Atualização simples (PATCH/MINOR)

```bash
cd /caminho/do/projeto
composer update vagkaefer/cms-filament
php artisan migrate
php artisan optimize:clear
```

### Cenário 2: Atualização com breaking changes (MAJOR)

```bash
# 1. Ler as release notes
# 2. Backup
php artisan backup:run
php artisan down

# 3. Ajustar o constraint no composer.json
#    "vagkaefer/cms-filament": "^2.0"
composer update vagkaefer/cms-filament

# 4. Seguir o guia de migração da versão
# 5. Republicar os configs, se houver novas chaves
php artisan vendor:publish --tag=cms-filament-config --force

# 6. Migrations e testes
php artisan migrate
php artisan test

php artisan up
```

### Cenário 3: Rollback

```bash
composer require vagkaefer/cms-filament:0.1.0
php artisan migrate:rollback   # se a versão nova trouxe migrations
php artisan backup:restore
```

## Proteções Contra Breaking Changes

### 1. Constraints de versão

No `composer.json` do projeto consumidor:

```json
{
    "require": {
        "vagkaefer/cms-filament": "^0.1"
    }
}
```

Na série `0.x`, `^0.1` aceita `0.1.x` e rejeita `0.2.0`. A partir da v1.0.0,
`^1.0` aceita toda a série `1.x` e rejeita `2.0.0`.

### 2. Lock file

O `composer.lock` do projeto consumidor garante que todos os ambientes usem a
mesma versão. Deve ser commitado.

### 3. CI com testes

Os 5 workflows do GitHub Actions (`pint`, `phpcs`, `phpms`, `phpstan`,
`phpunit`) rodam em todo push e pull request. Localmente, `./ci-local.sh`
espelha o CI.

## Matriz de Compatibilidade

| CMS Filament | PHP   | Laravel  | Filament |
| ------------ | ----- | -------- | -------- |
| 0.1.x        | ^8.4  | 12 / 13  | ^5.0     |

## Tags de Publish

| Tag                         | Conteúdo             |
| --------------------------- | -------------------- |
| `cms-filament-config`       | Arquivos de config   |
| `cms-filament-migrations`   | Migrations           |

## Checklist de Release

- [ ] `./ci-local.sh` sem falhas
- [ ] `CHANGELOG.md` atualizado com data da release
- [ ] `README.md` atualizado (se necessário)
- [ ] Breaking changes documentados
- [ ] Guia de migração escrito (para MAJOR)
- [ ] Matriz de compatibilidade conferida
- [ ] Versão testada em um projeto real
- [ ] Tag `vX.Y.Z` criada na `main`
- [ ] Merge-back em `develop`
- [ ] Release notes publicadas

## Recursos Adicionais

- [Semantic Versioning](https://semver.org/)
- [Keep a Changelog](https://keepachangelog.com/)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Git Flow](https://nvie.com/posts/a-successful-git-branching-model/)
