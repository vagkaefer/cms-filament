<?php

namespace VagKaefer\CmsFilament\Filament\Resources\Permissions\Pages;

use VagKaefer\CmsFilament\Filament\Resources\Permissions\PermissionResource;
use VagKaefer\CmsFilament\Filament\Traits\HasResourcePermissions;
use VagKaefer\CmsFilament\Models\Permission;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\File;

class ManagePermissions extends ManageRecords
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Atualizar')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->dispatch('$refresh');
                }),
            Action::make('gerar_dos_recursos')
                ->label('Gerar dos Recursos')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Gerar Permissões dos Recursos')
                ->modalDescription('Isso irá criar permissões automaticamente para todos os recursos '
                    . 'do Filament que usam o trait HasResourcePermissions.')
                ->modalSubmitActionLabel('Gerar Permissões')
                ->action(function () {
                    $createdCount = 0;
                    $existingCount = 0;

                    $resources = $this->getResourcesWithPermissionsTrait();

                    foreach ($resources as $resourceClass) {
                        if (! method_exists($resourceClass, 'getRequiredPermissions')) {
                            continue;
                        }

                        $permissions = $resourceClass::getRequiredPermissions();

                        foreach (array_keys($permissions) as $name) {
                            $permission = Permission::firstOrCreate(
                                ['name' => $name],
                                ['guard_name' => 'web']
                            );

                            if ($permission->wasRecentlyCreated) {
                                $createdCount++;
                            } else {
                                $existingCount++;
                            }
                        }
                    }

                    Notification::make()
                        ->title('Permissões geradas com sucesso!')
                        ->body("Criadas: {$createdCount} | Já existiam: {$existingCount}")
                        ->success()
                        ->send();
                })
                ->visible(fn (): bool => cms_filament_is_admin()),
            CreateAction::make()
                ->visible(fn () => static::getResource()::canCreate()),
        ];
    }

    /**
     * @return string[]
     */
    protected function getResourcesWithPermissionsTrait(): array
    {
        $resourcesPath = app_path('Filament/Resources');

        if (! File::exists($resourcesPath)) {
            return [];
        }

        $resources = [];
        $files = File::allFiles($resourcesPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // Skip Pages, Widgets, and other non-Resource files
            if (
                str_contains($file->getPath(), 'Pages') ||
                str_contains($file->getPath(), 'Widgets') ||
                ! str_ends_with($file->getFilename(), 'Resource.php')
            ) {
                continue;
            }

            $namespace = 'App\\Filament\\Resources';
            $relativePath = str_replace(
                [$resourcesPath, '/', '.php'],
                ['', '\\', ''],
                $file->getPathname()
            );

            $class = $namespace . $relativePath;

            if (! class_exists($class)) {
                continue;
            }

            // Check if the class uses HasResourcePermissions trait
            $traits = class_uses_recursive($class);
            if (in_array(HasResourcePermissions::class, $traits)) {
                $resources[] = $class;
            }
        }

        return $resources;
    }
}
