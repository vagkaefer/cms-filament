<?php

namespace VagKaefer\CmsFilament\Filament\Resources\Configurations\Pages;

use VagKaefer\CmsFilament\Filament\Resources\Configurations\ConfigurationResource;
use VagKaefer\CmsFilament\Filament\Traits\RedirectsToIndex;
use Filament\Resources\Pages\CreateRecord;

class CreateConfiguration extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = ConfigurationResource::class;
}
