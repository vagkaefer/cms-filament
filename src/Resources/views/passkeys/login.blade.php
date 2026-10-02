{{--
    Botão "Entrar com passkey", abaixo do formulário de login do painel.

    Fica fora do formulário Livewire de propósito: a entrada é um POST comum
    para o PasskeyLoginController, que autentica e redireciona. Some quando o
    navegador não suporta WebAuthn.
--}}
@php
    use VagKaefer\CmsFilament\Http\Controllers\PasskeyLoginController;

    $panel = filament()->getCurrentOrDefaultPanel();
@endphp

<div
    x-data="{
        supported: false,
        loading: false,
        failed: false,
        init() {
            this.supported = window.SimpleWebAuthnBrowser?.browserSupportsWebAuthn() ?? false
        },
        async login() {
            this.loading = true
            this.failed = false

            try {
                const response = await fetch(@js($panel->route('cms-filament.passkeys.options')), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                })
                const optionsJSON = await response.json()
                const assertion = await window.SimpleWebAuthnBrowser.startAuthentication({ optionsJSON })

                this.$refs.response.value = JSON.stringify(assertion)
                this.$refs.form.submit()
            } catch (error) {
                this.loading = false
                // Cancelar a janela do navegador não é erro.
                this.failed = error?.name !== 'NotAllowedError' && error?.name !== 'AbortError'
            }
        },
    }"
    x-show="supported"
    x-cloak
    style="display: flex; flex-direction: column; gap: 1rem;"
>
    <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.875rem; color: var(--gray-500);">
        <span style="flex: 1; height: 1px; background: currentColor; opacity: 0.3;"></span>
        ou
        <span style="flex: 1; height: 1px; background: currentColor; opacity: 0.3;"></span>
    </div>

    <x-filament::button
        type="button"
        color="gray"
        outlined
        :icon="\Filament\Support\Icons\Heroicon::OutlinedFingerPrint"
        x-on:click="login"
        x-bind:disabled="loading"
        style="width: 100%;"
    >
        Entrar com passkey
    </x-filament::button>

    @if (session()->has(PasskeyLoginController::ERROR_SESSION_KEY))
        <p style="font-size: 0.875rem; color: var(--danger-600); text-align: center;" x-show="! failed">
            {{ session(PasskeyLoginController::ERROR_SESSION_KEY) }}
        </p>
    @endif

    <p style="font-size: 0.875rem; color: var(--danger-600); text-align: center;" x-show="failed" x-cloak>
        Não foi possível usar a passkey. Tente de novo.
    </p>

    <form x-ref="form" method="POST" action="{{ $panel->route('cms-filament.passkeys.login') }}" style="display: none;">
        @csrf
        <input type="hidden" name="start_authentication_response" x-ref="response">
    </form>
</div>
