<?php

namespace VagKaefer\CmsFilament\Models;

use VagKaefer\CmsFilament\Exceptions\ProtectedRoleException;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * Papel administrativo da instalação.
     *
     * É a chave do bypass de autorização (`Gate::before`): sem ele não há
     * como administrar o painel. O nome pode ser trocado em
     * `cms-filament.admin_role`.
     */
    public const ADMIN = 'admin';

    protected $hidden = [
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $fillable = [
        'pivot',
        'name',
        'guard_name',
    ];

    /**
     * Impede a exclusão e a renomeação do papel `admin`.
     *
     * A trava fica no model — e não só na UI — porque excluir o `admin` deixa a
     * instalação sem ninguém capaz de administrar o painel, sem caminho de
     * volta pela interface. Aqui ela vale também para tinker, seeders, bulk
     * actions e qualquer código do projeto consumidor.
     */
    protected static function booted(): void
    {
        static::updating(function (self $role): void {
            // Compara com o nome ORIGINAL: durante a renomeação `$role->name`
            // já carrega o valor novo, e isProtected() não reconheceria o
            // papel que está sendo renomeado.
            //
            // Só o nome é travado: as permissões do papel seguem editáveis.
            $protected = static::protectedName();

            if ($protected === $role->getOriginal('name') && $role->isDirty('name')) {
                throw ProtectedRoleException::cannotRename($protected);
            }
        });
    }

    /**
     * Barra a exclusão do papel protegido.
     *
     * Sobrescreve `delete()` em vez de escutar o evento `deleting` para não
     * depender da ordem de registro dos listeners: o `bootHasPermissions()` do
     * Spatie também escuta `deleting` e ali faz `users()->detach()`. Barrar na
     * entrada do método é determinístico — nada é desassociado antes de a
     * exception subir.
     */
    public function delete(): bool
    {
        if ($this->isProtected()) {
            throw ProtectedRoleException::cannotDelete((string) $this->name);
        }

        return parent::delete();
    }

    /**
     * Se o papel é protegido contra exclusão e renomeação.
     */
    public function isProtected(): bool
    {
        return static::protectedName() === $this->name;
    }

    /**
     * Nome do cargo protegido nesta instalação.
     *
     * Configurável porque nem todo projeto chama de "admin", mas o conceito
     * (o cargo que administra o painel) é sempre um só.
     */
    public static function protectedName(): string
    {
        return (string) config('cms-filament.admin_role', self::ADMIN);
    }
}
