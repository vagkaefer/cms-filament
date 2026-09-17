<?php

namespace VagKaefer\CmsFilament\Filament\Resources\Roles\Pages;

use VagKaefer\CmsFilament\Filament\Resources\Roles\RoleResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Atualizar')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->dispatch('$refresh');
                }),
            CreateAction::make()
                ->visible(fn () => static::getResource()::canCreate()),
        ];
    }
}
