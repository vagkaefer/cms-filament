<?php

namespace VagKaefer\CmsFilament\Filament\Resources\Configurations\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use VagKaefer\CmsFilament\Filament\Resources\Configurations\ConfigurationResource;
use VagKaefer\CmsFilament\Filament\Traits\RedirectsToIndex;
use VagKaefer\CmsFilament\Models\Configuration;

class EditConfiguration extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = ConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Campo sensível deixado em branco não apaga o segredo guardado.
     *
     * O formulário nunca exibe o valor criptografado, então salvar sem digitar
     * nada significa "não mexer" — e não "gravar vazio". Retirar a chave do
     * array impede o mutator do model de rodar.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Configuration $record */
        $record = $this->getRecord();

        $name = $data['name'] ?? $record->name;

        if (in_array($name, Configuration::encryptedKeys(), true) && blank($data['value'] ?? null)) {
            unset($data['value']);
        }

        return $data;
    }
}
