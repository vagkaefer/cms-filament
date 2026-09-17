<?php

namespace VagKaefer\CmsFilament\Filament\Traits;

trait RedirectsToIndex
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
