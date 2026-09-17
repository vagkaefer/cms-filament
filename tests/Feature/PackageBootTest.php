<?php

namespace VagKaefer\CmsFilament\Tests\Feature;

use VagKaefer\CmsFilament\Models\Audit;
use VagKaefer\CmsFilament\Models\Permission;
use VagKaefer\CmsFilament\Models\Role;
use VagKaefer\CmsFilament\Tests\Fixtures\User;
use VagKaefer\CmsFilament\Tests\TestCase;

/**
 * Smoke test do ServiceProvider dentro de uma app Laravel (via Testbench).
 */
class PackageBootTest extends TestCase
{
    public function testConfigIsMerged(): void
    {
        $this->assertIsArray(config('cms-filament'));
        $this->assertSame('admin', config('cms-filament.admin_role'));
        $this->assertSame('Administração', config('cms-filament.navigation_group'));
    }

    /**
     * Sem esta troca o Spatie usaria os próprios models e a instalação perderia
     * a auditoria de cargos e a proteção do cargo administrativo.
     */
    public function testVendorModelsPointToThePackageModels(): void
    {
        $this->assertSame(Role::class, config('permission.models.role'));
        $this->assertSame(Permission::class, config('permission.models.permission'));
        $this->assertSame(Audit::class, config('audit.implementation'));
    }

    public function testHelpersAreAvailable(): void
    {
        $this->assertTrue(function_exists('app_version'));
        $this->assertTrue(function_exists('cms_filament_version'));
        $this->assertTrue(function_exists('cms_filament_user_model'));
        $this->assertTrue(function_exists('cms_filament_is_admin'));

        $this->assertSame(User::class, cms_filament_user_model());
        $this->assertFalse(cms_filament_is_admin());
    }

    public function testViewNamespaceIsRegistered(): void
    {
        $this->assertTrue(view()->exists('cms-filament::filament.footer'));
        $this->assertTrue(view()->exists('cms-filament::filament.widgets.version'));
    }
}
