<?php

namespace VagKaefer\CmsFilament\Tests\Feature;

use VagKaefer\CmsFilament\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

/**
 * Cobre o comando permissions:generate-resources ponta a ponta: um Resource do
 * projeto consumidor que usa o trait distribuído pelo package
 * (VagKaefer\CmsFilament\Filament\Traits\HasResourcePermissions) precisa ser
 * descoberto e ter suas 4 permissões criadas.
 *
 * Antes desta correção o comando importava App\Filament\Traits\HasResourcePermissions
 * e App\Models\Permission — namespaces que não existem no consumidor. O
 * in_array() do trait nunca casava ("No resources found"), e o firstOrCreate
 * estouraria "Class App\Models\Permission not found" assim que casasse.
 */
class GenerateResourcePermissionsCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $resourcesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resourcesPath = app_path('Filament/Resources');
        File::ensureDirectoryExists($this->resourcesPath);
        File::put($this->resourcesPath . '/SupporterResource.php', $this->fixtureSource());

        require_once $this->resourcesPath . '/SupporterResource.php';
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(app_path('Filament'));

        parent::tearDown();
    }

    /**
     * Resource mínimo nos moldes do que um consumidor escreve: usa o trait do
     * package e expõe getPluralModelLabel(), de que getRequiredPermissions()
     * depende para montar os rótulos.
     */
    private function fixtureSource(): string
    {
        return <<<'PHP'
        <?php

        namespace App\Filament\Resources;

        use VagKaefer\CmsFilament\Filament\Traits\HasResourcePermissions;

        class SupporterResource
        {
            use HasResourcePermissions;

            public static function getPluralModelLabel(): string
            {
                return 'Apoiadores';
            }
        }
        PHP;
    }

    public function testDiscoversConsumerResourceUsingThePackageTrait(): void
    {
        $this->artisan('permissions:generate-resources')
            ->expectsOutputToContain('com a trait.')
            ->assertSuccessful();
    }

    public function testCreatesTheFourPermissionsMatchingTheRuntimePrefix(): void
    {
        $this->artisan('permissions:generate-resources')->assertSuccessful();

        foreach (['view_supporter', 'create_supporter', 'edit_supporter', 'delete_supporter'] as $name) {
            $this->assertDatabaseHas('permissions', ['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function testIsIdempotentOnSecondRun(): void
    {
        $this->artisan('permissions:generate-resources')->assertSuccessful();
        $this->artisan('permissions:generate-resources')
            ->expectsOutputToContain('Criadas: 0')
            ->assertSuccessful();

        // 4 do resource do consumidor + 4 de cada resource do package que
        // usa a trait (usuários, cargos, permissões).
        $this->assertGreaterThanOrEqual(8, \VagKaefer\CmsFilament\Models\Permission::query()->count());
    }
}
