<?php

namespace VagKaefer\CmsFilament\Filament\Resources\Audits\Pages;

use Tapp\FilamentAuditing\Filament\Resources\Audits\Pages\ListAudits as BaseListAudits;
use VagKaefer\CmsFilament\Filament\Resources\Audits\AuditResource;

class ListAudits extends BaseListAudits
{
    protected static string $resource = AuditResource::class;
}
