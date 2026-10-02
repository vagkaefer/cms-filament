<?php

namespace VagKaefer\CmsFilament\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\LaravelPasskeys\Livewire\PasskeysComponent;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;

/**
 * Cadastro e remoção das passkeys do usuário logado, na página de perfil.
 *
 * A parte WebAuthn é a do componente do spatie: `validatePasskeyProperties()`
 * gera as opções e dispara `passkeyPropertiesValidated` para o navegador, e o
 * script da view devolve a credencial em `storePasskey()`. Aqui fica a parte
 * de tela: as ações do Filament e as notificações.
 */
class PasskeysManager extends PasskeysComponent implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public function render(): View
    {
        /** @var view-string $view */
        $view = 'cms-filament::passkeys.manager';

        return view($view, [
            'passkeys' => $this->currentUser()->passkeys()->latest()->get(),
        ]);
    }

    public function addPasskeyAction(): Action
    {
        return Action::make('addPasskey')
            ->label('Adicionar passkey')
            ->icon(Heroicon::OutlinedFingerPrint)
            ->color('gray')
            ->modalHeading('Adicionar passkey')
            ->modalWidth(Width::Medium)
            ->modalDescription(
                'Dê um nome para reconhecer este aparelho depois. '
                . 'Em seguida o navegador pede a digital, o rosto ou o PIN.'
            )
            ->modalSubmitActionLabel('Continuar')
            ->schema([
                TextInput::make('name')
                    ->label('Nome')
                    ->placeholder('Ex.: Notebook do trabalho, iPhone')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (array $data): void {
                $this->name = $data['name'];
                $this->validatePasskeyProperties();
            });
    }

    public function deletePasskeyAction(): Action
    {
        return Action::make('deletePasskey')
            ->label('Remover')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->link()
            ->requiresConfirmation()
            ->modalHeading('Remover passkey')
            ->modalDescription('Este aparelho deixa de entrar no painel sem senha. Dá para cadastrar de novo depois.')
            ->modalSubmitActionLabel('Remover')
            ->action(function (array $arguments): void {
                $this->deletePasskey($arguments['passkey'] ?? 0);

                Notification::make()
                    ->title('Passkey removida.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Grava a credencial devolvida pelo navegador.
     *
     * O spatie avisa a falha com um erro de validação num error bag que esta
     * tela não mostra. Aqui ela vira notificação.
     */
    public function storePasskey(string $passkey): void
    {
        try {
            parent::storePasskey($passkey);
        } catch (ValidationException) {
            Notification::make()
                ->title('Não foi possível cadastrar a passkey.')
                ->body('Tente de novo. Se continuar, confira se o navegador e o aparelho suportam passkeys.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Passkey cadastrada.')
            ->body('Na próxima vez, use "Entrar com passkey" na tela de login.')
            ->success()
            ->send();
    }

    public function currentUser(): Authenticatable&HasPasskeys
    {
        /** @var Authenticatable&HasPasskeys $user */
        $user = Filament::auth()->user();

        return $user;
    }
}
