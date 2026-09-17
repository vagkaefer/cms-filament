<?php

namespace VagKaefer\CmsFilament\Console\Commands;

use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use VagKaefer\CmsFilament\CmsFilamentPlugin;
use VagKaefer\CmsFilament\Filament\Traits\HasResourcePermissions;
use VagKaefer\CmsFilament\Models\Permission;

class GenerateResourcePermissions extends Command
{
    protected $signature = 'permissions:generate-resources';

    protected $description = 'Cria as permissões de todos os Filament Resources que usam a trait HasResourcePermissions';

    public function handle(): int
    {
        $this->info('Procurando Filament Resources...');

        $resources = $this->resourcesWithPermissionsTrait();

        if ($resources === []) {
            $this->warn('Nenhum resource encontrado com a trait HasResourcePermissions.');

            return self::SUCCESS;
        }

        $this->info('Encontrados ' . count($resources) . ' resource(s) com a trait.');

        $createdCount = 0;
        $existingCount = 0;

        foreach ($resources as $resourceClass) {
            $this->line('');
            $this->info('Processando: ' . class_basename($resourceClass));

            if (! method_exists($resourceClass, 'getRequiredPermissions')) {
                $this->warn('  Ignorado: método getRequiredPermissions não encontrado');

                continue;
            }

            foreach ($resourceClass::getRequiredPermissions() as $name => $label) {
                $permission = Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                );

                if ($permission->wasRecentlyCreated) {
                    $this->info("  ✓ Criada: {$name} ({$label})");
                    $createdCount++;

                    continue;
                }

                $this->comment("  - Já existia: {$name}");
                $existingCount++;
            }
        }

        $this->line('');
        $this->info('Resumo:');
        $this->info("  Criadas: {$createdCount}");
        $this->info("  Existentes: {$existingCount}");

        return self::SUCCESS;
    }

    /**
     * Todos os resources que usam a trait de permissões.
     *
     * Três fontes, unidas e deduplicadas, porque nenhuma isolada cobre todos
     * os cenários: a lista do plugin funciona mesmo sem painel montado (útil em
     * testes e no `db:seed`), os painéis registrados trazem resources de
     * qualquer namespace configurado pelo projeto, e a varredura de diretório
     * pega resources do app quando o painel ainda não foi resolvido.
     *
     * @return array<int, class-string>
     */
    protected function resourcesWithPermissionsTrait(): array
    {
        $candidates = array_merge(
            CmsFilamentPlugin::resources(),
            $this->panelResources(),
            $this->appResources(),
        );

        $resources = array_values(array_unique($candidates));

        return array_values(array_filter(
            $resources,
            fn (string $class): bool => class_exists($class)
                && in_array(HasResourcePermissions::class, class_uses_recursive($class), true)
        ));
    }

    /**
     * Resources registrados nos painéis Filament da aplicação.
     *
     * @return array<int, class-string>
     */
    protected function panelResources(): array
    {
        if (! class_exists(Filament::class)) {
            return [];
        }

        try {
            $resources = [];

            foreach (Filament::getPanels() as $panel) {
                $resources = array_merge($resources, array_values($panel->getResources()));
            }

            return $resources;
        } catch (\Throwable $e) {
            // Sem contexto de painel (console sem panels registrados) as outras
            // fontes bastam.
            return [];
        }
    }

    /**
     * Resources do projeto consumidor em app/Filament/Resources.
     *
     * @return array<int, class-string>
     */
    protected function appResources(): array
    {
        $resourcesPath = app_path('Filament/Resources');

        if (! File::exists($resourcesPath)) {
            return [];
        }

        $resources = [];

        foreach (File::allFiles($resourcesPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            if (
                str_contains($file->getPath(), 'Pages')
                || str_contains($file->getPath(), 'Widgets')
                || ! str_ends_with($file->getFilename(), 'Resource.php')
            ) {
                continue;
            }

            $relativePath = str_replace(
                [$resourcesPath, '/', '.php'],
                ['', '\\', ''],
                $file->getPathname()
            );

            $resources[] = 'App\\Filament\\Resources' . $relativePath;
        }

        return $resources;
    }
}
