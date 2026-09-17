<?php

namespace VagKaefer\CmsFilament\Filament\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class UserExporter extends Exporter
{
    public static function getModel(): string
    {
        return cms_filament_user_model();
    }

    /**
     * Colunas exportadas, espelhando a tabela do UserResource.
     *
     * @return array<ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('Nome'),

            ExportColumn::make('email')
                ->label('E-mail'),

            ExportColumn::make('active')
                ->label('Ativo')
                ->formatStateUsing(fn (mixed $state): string => $state ? 'Sim' : 'Não'),

            ExportColumn::make('roles.name')
                ->label('Cargos'),

            ExportColumn::make('created_at')
                ->label('Criado em'),

            ExportColumn::make('updated_at')
                ->label('Atualizado em'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'A exportação de usuários foi concluída e '
            . number_format($export->successful_rows)
            . ' linha(s) foram exportadas.';

        $failedRowsCount = $export->getFailedRowsCount();

        if ($failedRowsCount) {
            $body .= ' ' . number_format($failedRowsCount) . ' linha(s) falharam ao exportar.';
        }

        return $body;
    }
}
