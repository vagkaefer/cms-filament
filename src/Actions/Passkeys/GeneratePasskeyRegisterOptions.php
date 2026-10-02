<?php

namespace VagKaefer\CmsFilament\Actions\Passkeys;

use Cose\Algorithms;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;
use Spatie\LaravelPasskeys\Models\Passkey;
use Spatie\LaravelPasskeys\Support\Serializer;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;

/**
 * Opções de cadastro de passkey, com duas coisas que o spatie deixa de fora.
 *
 * - **Algoritmos explícitos.** O spatie manda a lista vazia, e cada gerenciador
 *   de senhas completa do seu jeito. O Bitwarden, por exemplo, só aceita ES256.
 *   ES256 vai primeiro, e RS256 fica para o Windows Hello.
 * - **Passkeys já cadastradas** em `excludeCredentials`: o aparelho que já tem
 *   uma passkey desta conta recusa criar outra, em vez de duplicar.
 */
class GeneratePasskeyRegisterOptions extends GeneratePasskeyRegisterOptionsAction
{
    public function execute(
        HasPasskeys $authenticatable,
        bool $asJson = true,
    ): string|PublicKeyCredentialCreationOptions {
        $options = new PublicKeyCredentialCreationOptions(
            rp: $this->relatedPartyEntity(),
            user: $this->generateUserEntity($authenticatable),
            challenge: $this->challenge(),
            pubKeyCredParams: [
                PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_ES256),
                PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_RS256),
            ],
            authenticatorSelection: $this->authenticatorSelection(),
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            excludeCredentials: $this->registeredCredentials($authenticatable),
        );

        return $asJson ? Serializer::make()->toJson($options) : $options;
    }

    /**
     * @return array<int, PublicKeyCredentialDescriptor>
     */
    private function registeredCredentials(HasPasskeys $authenticatable): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Passkey> $passkeys */
        $passkeys = $authenticatable->passkeys()->get();

        return $passkeys
            ->map(fn (Passkey $passkey): PublicKeyCredentialDescriptor => PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $passkey->data->publicKeyCredentialId,
            ))
            ->values()
            ->all();
    }
}
