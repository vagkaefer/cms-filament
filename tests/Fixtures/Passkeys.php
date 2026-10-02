<?php

namespace VagKaefer\CmsFilament\Tests\Fixtures;

use Spatie\LaravelPasskeys\Models\Passkey;
use Spatie\LaravelPasskeys\Support\CredentialRecordConverter;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * Passkeys gravadas direto no banco, sem cerimônia WebAuthn.
 *
 * A chave pública é a mesma da factory do spatie, que não dá para usar aqui
 * porque cria o usuário com `User::factory()`.
 */
class Passkeys
{
    private const PUBLIC_KEY = 'pQECAyYgASFYIJV56vRrFusoDf9hm3iDmllcxxXzzKyO9WruKw4kWx7zIlgg/'
        . 'nq63l8IMJcIdKDJcXRh9hoz0L+nVwP1Oxil3/oNQYs=';

    public static function create(User $user, string $name = 'Notebook'): Passkey
    {
        /** @var Passkey $passkey */
        $passkey = $user->passkeys()->create([
            'name' => $name,
            'data' => self::credentialSource(random_bytes(32)),
        ]);

        return $passkey;
    }

    private static function credentialSource(string $credentialId): PublicKeyCredentialSource
    {
        return CredentialRecordConverter::toPublicKeyCredentialSource(PublicKeyCredentialSource::create(
            $credentialId,
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            [],
            'none',
            EmptyTrustPath::create(),
            Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            (string) base64_decode(self::PUBLIC_KEY, true),
            'foo',
            100,
        ));
    }
}
