<?php

namespace VagKaefer\CmsFilament\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use VagKaefer\CmsFilament\Livewire\PasskeysManager;

/**
 * Perfil do Filament com as passkeys abaixo do segundo fator.
 *
 * Só é usado quando o painel não declara um perfil próprio. Um projeto com
 * perfil próprio acrescenta `Livewire::make(PasskeysManager::class)` no
 * `content()` dele.
 */
class EditProfile extends BaseEditProfile
{
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                ...Arr::wrap($this->getMultiFactorAuthenticationContentComponent()),
                Livewire::make(PasskeysManager::class),
            ]);
    }
}
