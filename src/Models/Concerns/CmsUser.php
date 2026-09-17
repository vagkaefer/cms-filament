<?php

namespace VagKaefer\CmsFilament\Models\Concerns;

use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Habilita um model de usuário do projeto consumidor no painel do package.
 *
 * Agrega cargos/permissões (Spatie), auditoria (owen-it) e a autenticação em
 * dois fatores nativa do Filament, além da flag `active` que controla o acesso
 * ao painel.
 *
 * O model que usa esta trait precisa declarar os contratos correspondentes:
 *
 *     use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
 *     use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
 *     use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
 *     use Filament\Models\Contracts\FilamentUser;
 *     use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
 *
 *     class User extends Authenticatable implements AuditableContract, FilamentUser,
 *         HasAppAuthentication, HasAppAuthenticationRecovery, HasEmailAuthentication
 *     {
 *         use CmsUser;
 *     }
 *
 * As colunas exigidas (`active`, `app_authentication_secret`,
 * `app_authentication_recovery_codes`, `has_email_authentication`) são criadas
 * pela migration do package.
 *
 * @property bool $active
 *
 * @mixin Model
 */
trait CmsUser
{
    use Auditable;
    use HasRoles;
    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;
    use InteractsWithEmailAuthentication;

    /**
     * Acrescenta cast e mass assignment da flag `active` sem exigir que o
     * projeto altere `casts()` ou `$fillable` — os merges do Eloquent são
     * aditivos, então atributos e propriedades do model são preservados.
     */
    protected function initializeCmsUser(): void
    {
        $this->mergeCasts([
            'active' => 'boolean',
        ]);

        $this->mergeFillable([
            'active',
        ]);
    }

    /**
     * Se a conta está habilitada.
     *
     * A coluna tem default `true`; o `?? true` cobre instâncias criadas em
     * memória (factories, testes) que ainda não passaram pelo banco.
     */
    public function isActive(): bool
    {
        return (bool) ($this->active ?? true);
    }

    /**
     * Quem entra no painel.
     *
     * Contas desativadas são barradas aqui — o Filament recusa o login e
     * devolve 403 para uma sessão já aberta. É por isso que o package não
     * precisa de middleware nem de tela "conta desativada".
     *
     * O projeto pode sobrescrever este método para restringir mais (por
     * exemplo, exigir um cargo específico).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isActive();
    }

    /**
     * Só o cargo administrativo personifica outros usuários.
     */
    public function canImpersonate(): bool
    {
        return $this->hasRole(config('cms-filament.admin_role', 'admin'));
    }

    /**
     * Contas desativadas não podem ser personificadas: personificar daria um
     * acesso que a própria conta não tem.
     */
    public function canBeImpersonated(): bool
    {
        return $this->isActive();
    }
}
