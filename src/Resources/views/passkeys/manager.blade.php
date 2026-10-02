{{-- O CSS do painel é o compilado do Filament, sem utilitários Tailwind: o visual vem dos componentes e de estilos próprios. --}}
<div>
    <style>
        .cms-passkeys-list { display: flex; flex-direction: column; gap: 0.75rem; margin: 0; padding: 0; list-style: none; }
        .cms-passkeys-item { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .cms-passkeys-name { font-size: 0.875rem; font-weight: 500; color: var(--gray-950); }
        .cms-passkeys-meta, .cms-passkeys-empty { font-size: 0.75rem; color: var(--gray-500); }
        .cms-passkeys-empty { font-size: 0.875rem; }
        :where(.dark) .cms-passkeys-name { color: #fff; }
        :where(.dark) .cms-passkeys-meta, :where(.dark) .cms-passkeys-empty { color: var(--gray-400); }
    </style>

    <x-filament::section
        compact
        secondary
        heading="Passkeys"
        description="Entre no painel com a digital, o rosto ou o PIN do aparelho, sem digitar a senha nem o código do segundo fator."
    >
        <x-slot name="afterHeader">
            {{ $this->addPasskeyAction }}
        </x-slot>

        @if ($passkeys->isEmpty())
            <p class="cms-passkeys-empty">Nenhuma passkey cadastrada.</p>
        @else
            <ul class="cms-passkeys-list">
                @foreach ($passkeys as $passkey)
                    <li class="cms-passkeys-item" wire:key="passkey-{{ $passkey->id }}">
                        <div>
                            <div class="cms-passkeys-name">{{ $passkey->name }}</div>
                            <div class="cms-passkeys-meta">
                                Cadastrada em {{ $passkey->created_at?->format('d/m/Y') }}
                                &middot;
                                {{ $passkey->last_used_at ? 'último uso ' . $passkey->last_used_at->diffForHumans() : 'ainda não usada' }}
                            </div>
                        </div>

                        {{ ($this->deletePasskeyAction)(['passkey' => $passkey->id]) }}
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>

    <x-filament-actions::modals />
</div>

@script
<script>
    $wire.on('passkeyPropertiesValidated', async (eventData) => {
        const passkeyOptions = eventData[0].passkeyOptions

        let passkey

        try {
            passkey = await window.SimpleWebAuthnBrowser.startRegistration({ optionsJSON: passkeyOptions })
        } catch (error) {
            // Cancelar a janela do navegador não é erro: só não cadastra.
            if (error?.name === 'NotAllowedError' || error?.name === 'AbortError') {
                return
            }

            new FilamentNotification()
                .title('Não foi possível cadastrar a passkey.')
                .body(error?.name === 'InvalidStateError'
                    ? 'Este aparelho já tem uma passkey cadastrada para esta conta.'
                    : 'O navegador recusou o cadastro. Tente de novo.')
                .danger()
                .send()

            return
        }

        $wire.storePasskey(JSON.stringify(passkey))
    })
</script>
@endscript
