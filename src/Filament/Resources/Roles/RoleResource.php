<?php

namespace VagKaefer\CmsFilament\Filament\Resources\Roles;

use BackedEnum;
use VagKaefer\CmsFilament\Filament\Resources\Roles\Pages\ManageRoles;
use VagKaefer\CmsFilament\Filament\Traits\HasResourcePermissions;
use VagKaefer\CmsFilament\Models\Role;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RoleResource extends Resource
{
    use HasResourcePermissions {
        canDelete as canDeleteByPermission;
    }

    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Cargos';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): UnitEnum | string | null
    {
        return config('cms-filament.navigation_group');
    }

    protected static ?string $modelLabel = 'Cargo';

    protected static ?string $pluralModelLabel = 'Cargos';

    /**
     * O papel `admin` não pode ser excluído nem por quem tem a permissão.
     *
     * A trava real está no model (`Role::booted()`); aqui ela apenas some com
     * o botão, para o usuário não esbarrar numa exception.
     */
    public static function canDelete(Model $record): bool
    {
        if ($record instanceof Role && $record->isProtected()) {
            return false;
        }

        return static::canDeleteByPermission($record);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getGlobalSearchResultTitle(object $record): string
    {
        return $record->name;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->regex('/^[a-z_-]+$/')
                    ->helperText('Apenas letras minúsculas, hífens (-) e underscores (_). '
                        . 'Exemplo: admin, editor-chefe, gestor_conteudo'),

                TextInput::make('guard_name')
                    ->label('Guard')
                    ->default('web')
                    ->required()
                    ->maxLength(255)
                    ->disabled()
                    ->dehydrated(),

                CheckboxList::make('permissions')
                    ->label('Permissões')
                    ->relationship('permissions', 'name')
                    ->columns(4)
                    ->searchable()
                    ->bulkToggleable()
                    ->columnSpanFull()
                    ->helperText('Selecione as permissões que este cargo terá.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('permissions.name')
                    ->label('Permissões')
                    ->badge()
                    ->separator(',')
                    ->wrap(),

                TextColumn::make('users_count')
                    ->label('Usuários Utilizando')
                    ->counts('users')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record) => static::canEdit($record)),
                DeleteAction::make()
                    ->visible(fn ($record) => static::canDelete($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => static::canDeleteAny())
                        // Sem isto o papel protegido entraria na seleção e a
                        // exclusão em massa morreria no meio, com exception.
                        ->authorizeIndividualRecords('delete'),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRoles::route('/'),
        ];
    }
}
