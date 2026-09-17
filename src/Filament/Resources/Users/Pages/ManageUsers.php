<?php

namespace VagKaefer\CmsFilament\Filament\Resources\Users\Pages;

use VagKaefer\CmsFilament\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

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
