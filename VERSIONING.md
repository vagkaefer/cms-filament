# Guia de Versionamento e Atualização

Este documento explica como gerenciar versões do CMS Filament e atualizar projetos sem quebrar nada.

## Estratégia de Versionamento

Usamos **[Semantic Versioning](https://semver.org/)**: `MAJOR.MINOR.PATCH`

### MAJOR (v2.0.0)

**Breaking changes** - Requer alterações nos projetos

**Exemplos:**

- Remover métodos públicos de Services
- Alterar estrutura de tabelas (migrations incompatíveis)
- Mudar assinatura de métodos
- Remover campos obrigatórios de configs
- Alterar namespaces

**Como fazer:**

```bash
# 1. Fazer as alterações
# 2. Documentar BREAKING CHANGES no CHANGELOG.md
# 3. Versionar
git tag v2.0.0 -m "BREAKING: Alteração na estrutura do ModuleBuilder"
git push origin v2.0.0
```

**Atualização nos projetos:**

```bash
# Requer atenção manual
composer require vagkaefer/cms-filament:^2.0
# Seguir guia de migração na release notes
```

### MINOR (v1.1.0)

**Novas funcionalidades** - Compatível com versões anteriores

**Exemplos:**

- Adicionar novos recursos Filament
- Novos métodos em Services (sem alterar existentes)
- Novos campos em configs (com valores padrão)
- Novos tipos de campo no ModuleBuilder
- Melhorias de performance

**Como fazer:**

```bash
git tag v1.1.0 -m "feat: Adiciona suporte a campo de arquivo PDF"
git push origin v1.1.0
```

**Atualização nos projetos:**

```bash
# Seguro, apenas atualizar
composer update vagkaefer/cms-filament
```

### PATCH (v1.0.1)

**Bug fixes** - Correções de bugs

**Exemplos:**

- Corrigir validações
- Corrigir geração de código
- Corrigir migrations
- Corrigir permissões

**Como fazer:**

```bash
git tag v1.0.1 -m "fix: Corrige validação de imagem no ModuleBuilder"
git push origin v1.0.1
```

**Atualização nos projetos:**

```bash
# Totalmente seguro
composer update vagkaefer/cms-filament
```

## Workflow de Release

### 1. Desenvolvimento

```bash
# Criar branch para feature/fix
git checkout -b feature/novo-recurso
# Desenvolver
git add .
git commit -m "feat: adiciona novo recurso X"
git push origin feature/novo-recurso
```

### 2. Code Review & Merge

```bash
# Após aprovação
git checkout main
git merge feature/novo-recurso
```

### 3. Atualizar CHANGELOG.md

```markdown
## [1.1.0] - 2025-12-02

### Added
- Suporte a campo de arquivo PDF no ModuleBuilder
- Novo recurso de exportação em massa

### Changed
- Melhorias de performance na geração de módulos

### Fixed
- Corrigido bug na validação de imagens
```

### 4. Criar Tag

```bash
# Para MINOR release
git tag v1.1.0 -m "Release v1.1.0 - Novos recursos"
git push origin v1.1.0

# Para criar release no GitHub
gh release create v1.1.0 \
  --title "v1.1.0 - Novos Recursos" \
  --notes "$(cat CHANGELOG.md | sed -n '/## \[1.1.0\]/,/## \[/p' | sed '$ d')"
```

## 🔄 Atualização de Projetos

### Cenário 1: Atualização Simples (PATCH/MINOR)

```bash
cd /caminho/do/projeto
composer update vagkaefer/cms-filament
php artisan migrate
php artisan optimize:clear
```

### Cenário 2: Atualização com Breaking Changes (MAJOR)

```bash
# 1. Ler release notes
# 2. Fazer backup
php artisan backup:run
php artisan down

# 3. Atualizar composer.json
"vagkaefer/cms-filament": "^2.0"

# 4. Atualizar
composer update vagkaefer/cms-filament

# 5. Seguir guia de migração específico da versão
# 6. Testar
php artisan test

# 7. Publicar novos configs (se houver)
php artisan vendor:publish --tag=cms-config --force

# 8. Executar migrations
php artisan migrate

# 9. Voltar online
php artisan up
```

### Cenário 3: Rollback

Se algo der errado:

```bash
# 1. Voltar para versão anterior
composer require vagkaefer/cms-filament:1.0.5

# 2. Reverter migrations (se necessário)
php artisan migrate:rollback

# 3. Restaurar backup
php artisan backup:restore
```

## 🛡️ Proteções Contra Breaking Changes

### 1. Usar Constraints de Versão

No `composer.json` do projeto:

```json
{
    "require": {
        "vagkaefer/cms-filament": "^1.0"
    }
}
```

Isso significa:

- ✅ Aceita: `1.0.x`, `1.1.x`, `1.9.x`
- ❌ Rejeita: `2.0.0` (protege contra breaking changes)

### 2. Lock File

O `composer.lock` garante que todos usem a mesma versão:

```bash
# Commit o composer.lock
git add composer.lock
git commit -m "Lock CMS Filament version"
```

### 3. CI/CD com Testes

Configure testes automatizados antes de fazer deploy:

```yaml
# .github/workflows/test.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run Tests
        run: |
          composer install
          php artisan test
```

## 📊 Matriz de Compatibilidade

| CMS Filament | Laravel | Filament | PHP  |
| -------- | ------- | -------- | ---- |
| 1.x      | ^12.0   | ^4.0     | ^8.5 |
| 2.x      | ^12.0   | ^4.0     | ^8.5 |

## 🚨 Checklist de Release

Antes de criar uma nova versão:

- [ ] Todos os testes passando
- [ ] CHANGELOG.md atualizado
- [ ] README.md atualizado (se necessário)
- [ ] Documentação atualizada
- [ ] Breaking changes documentados
- [ ] Migration guide criado (para MAJOR)
- [ ] Versão testada em projeto real
- [ ] Tag criada com mensagem descritiva
- [ ] Release notes publicadas

## 📝 Comunicação de Updates

### Para PATCH/MINOR (seguro)

Email/Slack:

```
📢 Nova versão disponível: CMS Filament v1.1.0

✨ Novidades:
- Suporte a campo PDF
- Melhorias de performance

🔧 Como atualizar:
composer update vagkaefer/cms-filament
php artisan migrate

📖 Changelog: https://github.com/vagkaefer/cms-filament/releases/v1.1.0
```

### Para MAJOR (atenção!)

Email/Slack:

```
⚠️ BREAKING CHANGES: CMS Filament v2.0.0

🚨 Esta versão requer atenção ao atualizar!

🔴 Breaking Changes:
- Alteração na estrutura do ModuleBuilder
- Novos campos obrigatórios em configs

📖 Guia de Migração OBRIGATÓRIO:
https://github.com/vagkaefer/cms-filament/wiki/Migrate-to-v2

⏰ Agende a atualização com cuidado!
```

## 🔍 Monitoramento

### Verificar versões em uso

```bash
# Criar script para verificar versões em todos os projetos
#!/bin/bash
for dir in /var/www/*/; do
    cd "$dir"
    VERSION=$(composer show vagkaefer/cms-filament | grep 'versions' | awk '{print $3}')
    echo "$dir: $VERSION"
done
```

### Dashboard de Versões

Considere criar um dashboard interno que mostra:

- Qual versão cada projeto está usando
- Alertas de versões desatualizadas
- Security advisories

## 📚 Recursos Adicionais

- [Semantic Versioning](https://semver.org/)
- [Keep a Changelog](https://keepachangelog.com/)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Git Flow](https://nvie.com/posts/a-successful-git-branching-model/)
