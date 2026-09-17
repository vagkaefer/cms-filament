<?php

namespace VagKaefer\CmsFilament\Filament\Widgets;

use Filament\Widgets\Widget;

class CmsVersionWidget extends Widget
{
    /**
     * @var view-string
     *
     * @phpstan-ignore-next-line property.defaultValue
     */
    protected string $view = 'cms-filament::filament.widgets.version';

    protected int | string | array $columnSpan = 'full';
}
