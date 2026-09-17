<?php

namespace VagKaefer\CmsFilament\Tests\Fixtures;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Foundation\Auth\User as Authenticatable;
use OwenIt\Auditing\Contracts\Auditable;
use VagKaefer\CmsFilament\Models\Concerns\CmsUser;

/**
 * Usuário de um projeto consumidor típico: tabela `users` padrão do Laravel,
 * id inteiro, usando a trait do package.
 */
class User extends Authenticatable implements Auditable, FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasEmailAuthentication
{
    use CmsUser;

    protected $table = 'users';

    /**
     * Declarado explicitamente (e não `$guarded = []`) porque a trait do
     * package faz mergeFillable: um model com fillable vazio passa a aceitar
     * só o que a trait acrescenta.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
