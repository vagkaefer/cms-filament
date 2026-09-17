<?php

namespace VagKaefer\CmsFilament\Filament\Resources\Configurations;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
use VagKaefer\CmsFilament\Filament\Resources\Configurations\Pages\CreateConfiguration;
use VagKaefer\CmsFilament\Filament\Resources\Configurations\Pages\EditConfiguration;
use VagKaefer\CmsFilament\Filament\Resources\Configurations\Pages\ListConfigurations;
use VagKaefer\CmsFilament\Filament\Resources\Configurations\Schemas\ConfigurationForm;
use VagKaefer\CmsFilament\Filament\Resources\Configurations\Tables\ConfigurationsTable;
use VagKaefer\CmsFilament\Models\Configuration;

class ConfigurationResource extends Resource
{
    protected static ?string $model = Configuration::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::Cog;

    protected static ?string $label = 'Configuração';

    protected static ?string $pluralLabel = 'Configurações';

    protected static ?int $navigationSort = 40;

    /**
     * Restrito ao cargo administrativo: a tela expõe credenciais da
     * instalação, não conteúdo do dia a dia.
     */
    public static function canViewAny(): bool
    {
        return cms_filament_is_admin();
    }

    public static function getNavigationGroup(): UnitEnum | string | null
    {
        return config('cms-filament.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return ConfigurationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConfigurationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConfigurations::route('/'),
            'create' => CreateConfiguration::route('/create'),
            'edit' => EditConfiguration::route('/{record}/edit'),
        ];
    }
}
