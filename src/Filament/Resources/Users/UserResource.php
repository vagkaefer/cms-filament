<?php

namespace VagKaefer\CmsFilament\Filament\Resources\Users;

use BackedEnum;
use VagKaefer\CmsFilament\Filament\Exports\UserExporter;
use VagKaefer\CmsFilament\Filament\Resources\Users\Pages\ManageUsers;
use VagKaefer\CmsFilament\Filament\Traits\HasResourcePermissions;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;
use VagKaefer\CmsFilament\CmsFilamentPlugin;
use VagKaefer\CmsFilament\Support\ProtectedUsers;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Dashboard;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use STS\FilamentImpersonate\Actions\Impersonate;

class UserResource extends Resource
{
    /**
     * Os `can*` do trait resolvem por permissão e dão bypass ao admin. Aqui
     * eles viram o segundo passo: a proteção das contas do fornecedor é
     * decidida antes, e só depois a permissão é consultada.
     */
    use HasResourcePermissions {
        canView as canViewByPermission;
        canEdit as canEditByPermission;
        canDelete as canDeleteByPermission;
        canForceDelete as canForceDeleteByPermission;
        canRestore as canRestoreByPermission;
        canReplicate as canReplicateByPermission;
    }

    /**
     * O model vem do projeto consumidor (`auth.providers.users.model`), por
     * isso a propriedade fica nula e quem responde é getModel().
     */
    protected static ?string $model = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $navigationLabel = 'Usuários';

    protected static ?int $navigationSort = 70;

    protected static ?string $modelLabel = 'Usuário';

    protected static ?string $pluralModelLabel = 'Usuários';

    public static function getModel(): string
    {
        return cms_filament_user_model();
    }

    public static function getNavigationGroup(): UnitEnum | string | null
    {
        return config('cms-filament.navigation_group');
    }

    /**
     * Se a exportação de usuários está ligada no plugin.
     *
     * Fica desligada por padrão: o export do Filament é assíncrono e exige as
     * migrations de `filament-actions`, tabela de notificações e um worker
     * ativo — infraestrutura que nem todo projeto tem.
     */
    public static function userExportEnabled(): bool
    {
        try {
            // Fora de uma requisição de painel (console, testes) não há painel
            // corrente, e o Filament lança exceção em vez de devolver null.
            $panel = Filament::getCurrentOrDefaultPanel();
        } catch (\Throwable $e) {
            return false;
        }

        if ($panel === null || ! $panel->hasPlugin('cms-filament')) {
            return false;
        }

        $plugin = $panel->getPlugin('cms-filament');

        return $plugin instanceof CmsFilamentPlugin && $plugin->hasUserExport();
    }

    /**
     * Esconde as contas protegidas da listagem e da busca global.
     *
     * Tudo que lê usuários no painel passa por aqui — tabela, `ManageRecords` e
     * global search —, por isso o filtro fica neste ponto e não em cada tela. O
     * Filament devolve a query sem restrição por padrão, o que exporia as
     * contas do fornecedor a qualquer admin do projeto consumidor.
     *
     * @return Builder<*>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        ProtectedUsers::scopeVisible($query);

        return $query;
    }

    /**
     * Se o registro é uma conta protegida fora do alcance de quem pergunta.
     *
     * Uma conta protegida segue administrável por outra conta protegida — é
     * assim que o fornecedor mantém as próprias contas, inclusive a si mesmo.
     */
    protected static function isOutOfReach(Model $record): bool
    {
        return ProtectedUsers::isProtected($record) && ProtectedUsers::hidesFrom();
    }

    public static function canView(Model $record): bool
    {
        if (static::isOutOfReach($record)) {
            return false;
        }

        return static::canViewByPermission($record);
    }

    public static function canEdit(Model $record): bool
    {
        if (static::isOutOfReach($record)) {
            return false;
        }

        return static::canEditByPermission($record);
    }

    public static function canDelete(Model $record): bool
    {
        if (static::isOutOfReach($record)) {
            return false;
        }

        return static::canDeleteByPermission($record);
    }

    public static function canForceDelete(Model $record): bool
    {
        if (static::isOutOfReach($record)) {
            return false;
        }

        return static::canForceDeleteByPermission($record);
    }

    public static function canRestore(Model $record): bool
    {
        if (static::isOutOfReach($record)) {
            return false;
        }

        return static::canRestoreByPermission($record);
    }

    public static function canReplicate(Model $record): bool
    {
        if (static::isOutOfReach($record)) {
            return false;
        }

        return static::canReplicateByPermission($record);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getGlobalSearchResultTitle(object $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(object $record): array
    {
        return [
            'Email' => $record->email,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('active')
                    ->label('Ativo')
                    ->default(true)
                    ->inline(false)
                    ->required(),

                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('password')
                    ->label('Senha')
                    ->password()
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255)
                    ->helperText('Deixe em branco para manter a senha atual ao editar.'),

                Select::make('roles')
                    ->label('Cargos')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText('Selecione um ou mais cargos para o usuário.'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label('Cargos')
                    ->badge()
                    ->separator(','),

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
                Impersonate::make()
                    // Closure, e não route() direto: a URL do dashboard depende
                    // do painel da requisição, que na montagem da tabela ainda
                    // pode não estar resolvido.
                    ->redirectTo(fn (): string => Dashboard::getUrl())
                    ->color('gray')
                    // Entrar como uma conta protegida é, na prática, vê-la por
                    // dentro: fica restrito a quem já pode administrá-la.
                    ->visible(fn (Model $record): bool => cms_filament_is_admin()
                        && ! static::isOutOfReach($record)),
                EditAction::make()
                    ->visible(fn (Model $record): bool => static::canEdit($record)),
                DeleteAction::make()
                    ->visible(fn (Model $record): bool => static::canDelete($record)),
            ])
            ->bulkActions([
                ExportBulkAction::make('exportar_selecionados')
                    ->label('Exportar Selecionados')
                    ->exporter(UserExporter::class)
                    ->fileName(fn (): string => 'usuarios_selecionados')
                    ->visible(fn (): bool => static::userExportEnabled()),
                DeleteBulkAction::make()
                    ->visible(fn (): bool => static::canDeleteAny())
                    // Sem isto a exclusão em massa não consulta o `canDelete` de
                    // cada linha e passaria por cima das contas protegidas.
                    ->authorizeIndividualRecords('delete'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
