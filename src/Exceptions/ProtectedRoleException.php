<?php

namespace VagKaefer\CmsFilament\Exceptions;

use RuntimeException;

/**
 * Tentativa de excluir ou renomear um papel protegido.
 *
 * Protege o papel `admin`, que é a chave do bypass de autorização
 * (`Gate::before`): sem ele a instalação fica sem ninguém capaz de administrar
 * o painel, e não há caminho de volta pela interface.
 */
class ProtectedRoleException extends RuntimeException
{
    public static function cannotDelete(string $name): self
    {
        return new self(
            "O papel \"{$name}\" é protegido e não pode ser excluído. "
            . 'Sem ele a instalação fica sem administrador.'
        );
    }

    public static function cannotRename(string $name): self
    {
        return new self(
            "O papel \"{$name}\" é protegido e não pode ser renomeado. "
            . 'O nome é usado nas checagens de autorização do painel.'
        );
    }
}
