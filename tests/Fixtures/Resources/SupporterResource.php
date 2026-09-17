<?php

namespace VagKaefer\CmsFilament\Tests\Fixtures\Resources;

use Filament\Resources\Resource;
use VagKaefer\CmsFilament\Filament\Traits\HasResourcePermissions;

/**
 * Resource de projeto consumidor que adota a trait de permissões.
 *
 * Prefixo derivado do nome da classe: `supporter`.
 */
class SupporterResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $modelLabel = 'Apoiador';

    protected static ?string $pluralModelLabel = 'Apoiadores';
}
