<?php

namespace VagKaefer\CmsFilament\Filament\Resources\Audits;

use VagKaefer\CmsFilament\Models\Audit;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use VagKaefer\CmsFilament\Support\ProtectedUsers;
use UnitEnum;
use Tapp\FilamentAuditing\Concerns\HasExtraColumns;
use Tapp\FilamentAuditing\Concerns\HasFormattedData;
use Tapp\FilamentAuditing\Filament\Resources\Audits\AuditResource as BaseAuditResource;
use Tapp\FilamentAuditing\Filament\Resources\Audits\Schemas\AuditFilters;
use Tapp\FilamentAuditing\Filament\Tables\Columns\AuditValuesColumn;

class AuditResource extends BaseAuditResource
{
    use HasExtraColumns;
    use HasFormattedData;

    protected static ?string $model = Audit::class;

    public static ?string $label = 'Auditoria';

    protected static ?int $navigationSort = 10;

    /**
     * Restrito ao cargo administrativo: a trilha mostra valores antigos e
     * novos de qualquer registro do sistema.
     */
    public static function canViewAny(): bool
    {
        return cms_filament_is_admin();
    }

    public static function getNavigationGroup(): UnitEnum | string | null
    {
        return config('cms-filament.navigation_group');
    }

    /**
     * Some com as linhas atribuídas às contas protegidas.
     *
     * A coluna `user.name` revelaria o nome delas a qualquer admin do projeto
     * consumidor, que é justamente quem não deve enxergá-las. As linhas sem
     * usuário (ações do próprio sistema) continuam visíveis.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder  $query
     */
    public static function hideProtectedUsersTrail($query): void
    {
        if (! ProtectedUsers::hidesFrom()) {
            return;
        }

        /** @var class-string<\Illuminate\Database\Eloquent\Model> $userModel */
        $userModel = cms_filament_user_model();
        $user = new $userModel();

        $protectedIds = $userModel::query()
            ->where(function ($query): void {
                foreach (ProtectedUsers::DOMAINS as $domain) {
                    $query->orWhere('email', 'like', '%@' . $domain);
                }
            })
            ->pluck($user->getKeyName());

        if ($protectedIds->isEmpty()) {
            return;
        }

        // O `whereNull` é obrigatório: em SQL, `user_id NOT IN (...)` é NULL —
        // e não verdadeiro — quando `user_id` é nulo, o que sumiria também com
        // as ações do próprio sistema.
        $query->where(function ($query) use ($protectedIds): void {
            $query->whereNull('user_id')
                ->orWhereNotIn('user_id', $protectedIds);
        });
    }

    public static function table(Table $table): Table
    {
        // Recria a tabela completamente para evitar carregar 'auditable' que pode não existir
        return $table
            ->modifyQueryUsing(function ($query) {
                // Não carrega 'auditable' pois a classe pode não existir mais
                $query->with(['user'])
                    ->orderBy(
                        config('filament-auditing.audits_sort.column'),
                        config('filament-auditing.audits_sort.direction')
                    );

                static::hideProtectedUsersTrail($query);
            })
            ->emptyStateHeading(trans('filament-auditing::filament-auditing.table.empty_state_heading'))
            ->columns(Arr::flatten([
                TextColumn::make('user.name')
                    ->label(trans('filament-auditing::filament-auditing.column.user_name')),
                TextColumn::make('auditable_type_display')
                    ->label(trans('filament-auditing::filament-auditing.column.auditable_type')),
                TextColumn::make('event')
                    ->label(trans('filament-auditing::filament-auditing.column.event')),
                TextColumn::make('created_at')
                    ->since()
                    ->label(trans('filament-auditing::filament-auditing.column.created_at')),
                AuditValuesColumn::make('old_values')
                    ->label(trans('filament-auditing::filament-auditing.column.old_values')),
                AuditValuesColumn::make('new_values')
                    ->label(trans('filament-auditing::filament-auditing.column.new_values')),
                self::extraColumns(),
            ]))
            ->filters(AuditFilters::configure())
            ->recordActions([
                ViewAction::make(),
                // RestoreAuditAction removido pois causa erro quando auditable não existe
            ])
            ->toolbarActions([])
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record->getAttribute('id')]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAudits::route('/'),
            'view' => Pages\ViewAudit::route('/{record}'),
        ];
    }
}
