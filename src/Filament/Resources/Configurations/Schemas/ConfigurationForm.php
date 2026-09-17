<?php

namespace VagKaefer\CmsFilament\Filament\Resources\Configurations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use VagKaefer\CmsFilament\Models\Configuration;

class ConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Campo')
                    ->disabledOn('edit')
                    ->required()
                    ->dehydrated(),
                TextInput::make('value')
                    ->label('Valor')
                    ->required()
                    ->rules(['nullable', 'string'])
                    ->dehydrated(fn ($state): bool => filled($state) || $state === null)
                    ->default(null)
                    ->hint(fn (?Configuration $record): ?string => $record?->isEncrypted()
                        ? 'Campo criptografado. Não é possível visualizar, somente editar.'
                        : null)
                    // Um valor criptografado nunca volta preenchido: deixar o
                    // campo vazio é o que sinaliza "manter o segredo atual".
                    ->formatStateUsing(fn (?Configuration $record): ?string => $record?->isEncrypted()
                        ? ''
                        : $record?->value)
                    ->hintColor('warning'),
            ]);
    }
}
