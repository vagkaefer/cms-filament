<?php

namespace VagKaefer\CmsFilament\Filament\Resources\Audits\Pages;

use Tapp\FilamentAuditing\Filament\Resources\Audits\Pages\ViewAudit as BaseViewAudit;

class ViewAudit extends BaseViewAudit
{
    // Customização movida para o modelo Audit via accessor auditableTypeDisplay
    // A visualização padrão já funciona corretamente com o accessor
}
