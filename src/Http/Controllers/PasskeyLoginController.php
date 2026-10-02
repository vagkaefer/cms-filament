<?php

namespace VagKaefer\CmsFilament\Http\Controllers;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Events\PasskeyUsedToAuthenticateEvent;
use Spatie\LaravelPasskeys\Http\Requests\AuthenticateUsingPasskeysRequest;
use Spatie\LaravelPasskeys\Support\Config;

/**
 * Entrada no painel com passkey.
 *
 * Faz o mesmo que o controller do spatie, com três diferenças:
 *
 * - entra pelo guard do painel;
 * - aplica `canAccessPanel()`, então conta desativada não entra, igual ao
 *   login com senha;
 * - volta para o painel, e não para um `redirect_to_after_login` fixo.
 *
 * O segundo fator (app autenticador / e-mail) não é pedido. Ele só existe no
 * login com senha: a passkey já é posse do aparelho + biometria ou PIN.
 */
class PasskeyLoginController
{
    public const ERROR_SESSION_KEY = 'cms-filament.passkeys.error';

    public function __invoke(AuthenticateUsingPasskeysRequest $request): RedirectResponse
    {
        $options = Session::pull('passkey-authentication-options');

        if (blank($options)) {
            return $this->failed();
        }

        $passkey = Config::getAction('find_passkey', FindPasskeyToAuthenticateAction::class)->execute(
            (string) $request->input('start_authentication_response'),
            $options,
        );

        $user = $passkey?->authenticatable;
        $panel = Filament::getCurrentOrDefaultPanel();

        if (! $user instanceof Authenticatable) {
            return $this->failed();
        }

        if ($user instanceof FilamentUser && ! $user->canAccessPanel($panel)) {
            return $this->failed();
        }

        Filament::auth()->login($user);
        $request->session()->regenerate();

        event(new PasskeyUsedToAuthenticateEvent($passkey, $request));

        return redirect()->intended(Filament::getUrl());
    }

    private function failed(): RedirectResponse
    {
        return redirect()
            ->to(Filament::getLoginUrl())
            ->with(self::ERROR_SESSION_KEY, 'Não foi possível entrar com esta passkey.');
    }
}
