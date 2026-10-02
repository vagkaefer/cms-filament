<?php

namespace VagKaefer\CmsFilament\Actions\Passkeys;

use Spatie\LaravelPasskeys\Actions\ConfigureCeremonyStepManagerFactoryAction as BaseAction;
use Spatie\LaravelPasskeys\Support\Config;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;

/**
 * Regras de origem das cerimônias WebAuthn (cadastro e login de passkey).
 *
 * Em produção fica o padrão do webauthn-lib: exige HTTPS e aceita o domínio do
 * Relying Party e os subdomínios dele. Com RP ID `exemplo.com.br`, entram
 * `https://exemplo.com.br`, `https://www.` e `https://novo.`.
 *
 * Em `localhost`, o navegador libera WebAuthn sem HTTPS, mas o webauthn-lib
 * recusava com "HTTPS required". Por isso a origem local (`http://localhost:8000`)
 * é liberada explicitamente.
 */
class ConfigurePasskeyCeremonies extends BaseAction
{
    public function execute(): CeremonyStepManagerFactory
    {
        $factory = parent::execute();

        if (Config::getRelyingPartyId() === 'localhost' && request()->getHost() === 'localhost') {
            $factory->setAllowedOrigins([request()->getSchemeAndHttpHost()]);
        }

        return $factory;
    }
}
