<?php

namespace VagKaefer\CmsFilament\Filament\Resources\Configurations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use VagKaefer\CmsFilament\Filament\Resources\Configurations\ConfigurationResource;
use VagKaefer\CmsFilament\Models\Configuration;

class ConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Campo')
                    ->searchable(),
                TextColumn::make('value')
                    ->label('Valor')
                    ->formatStateUsing(fn (Configuration $record): ?string => $record->isEncrypted()
                        ? '••••••••••••••••'
                        : $record->value)
                    ->badge()
                    ->icon(fn (Configuration $record): ?string => $record->isEncrypted()
                        ? 'heroicon-o-lock-closed'
                        : null)
                    ->color(fn (Configuration $record): string => $record->isEncrypted() ? 'danger' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (Configuration $record): bool => ConfigurationResource::canEdit($record)),
                DeleteAction::make()
                    ->visible(fn (Configuration $record): bool => ConfigurationResource::canDelete($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
