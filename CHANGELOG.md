# Changelog

Todas as mudanças relevantes deste package são documentadas aqui.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/)
e o versionamento segue [SemVer](https://semver.org/lang/pt-BR/).

## [0.1.0] - 2026-09-16

### Adicionado

- Plugin Filament (`CmsFilamentPlugin`) com recursos de Usuários, Cargos,
  Permissões, Auditoria e Configurações.
- Trait `CmsUser` para o model de usuário do projeto consumidor (cargos,
  auditoria, MFA nativo do Filament 5 e flag `active`).
- Autorização por permissão via trait `HasResourcePermissions`, com bypass
  para o cargo `admin`.
- Auditoria (owen-it), backups (spatie), visualizador de logs, indicador de
  ambiente e impersonate integrados ao painel.
- Comandos `permissions:generate-resources`, `logs:clean-old`,
  `logs:clean-large` e `cms-filament:ai-setup`.
