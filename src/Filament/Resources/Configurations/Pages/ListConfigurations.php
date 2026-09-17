<?php

namespace VagKaefer\CmsFilament\Filament\Resources\Configurations\Pages;

use VagKaefer\CmsFilament\Filament\Resources\Configurations\ConfigurationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConfigurations extends ListRecords
{
    protected static string $resource = ConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
