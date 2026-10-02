<?php

namespace VagKaefer\CmsFilament\Tests\Fixtures;

use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Models\Passkey;

/**
 * Faz o papel do navegador + autenticador: devolve a passkey que o teste
 * escolher, sem verificar assinatura. O que se testa é o que vem depois.
 */
class FakeFindPasskeyAction extends FindPasskeyToAuthenticateAction
{
    public static ?Passkey $passkey = null;

    public function execute(string $publicKeyCredentialJson, string $passkeyOptionsJson): ?Passkey
    {
        return static::$passkey;
    }
}
