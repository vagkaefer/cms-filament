# Changelog

Todas as mudanças relevantes deste package são documentadas aqui.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/)
e o versionamento segue [SemVer](https://semver.org/lang/pt-BR/).

## [0.1.2] - 2026-10-01

### Adicionado

- Login por passkey (WebAuthn) com spatie/laravel-passkeys. O botão "Entrar com
  passkey" fica no login e a gestão no Perfil, abaixo do segundo fator. O MFA
  nativo continua valendo para quem entra com senha. Liga quando o User declara
  `HasPasskeys`, e `->passkeys(false)` desliga.
- Tabela `passkeys`, que acompanha o tipo do id de `users` (inteiro ou uuid).
- `PASSKEYS_RP_ID` para fixar o domínio das passkeys.
- Opções de cadastro com algoritmos explícitos (ES256, RS256), porque o
  Bitwarden só aceita ES256, e com as passkeys já cadastradas excluídas.

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
