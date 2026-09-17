<?php

namespace VagKaefer\CmsFilament\Tests\Feature;

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Panel;
use VagKaefer\CmsFilament\CmsFilamentPlugin;
use VagKaefer\CmsFilament\Filament\Resources\Audits\AuditResource;
use VagKaefer\CmsFilament\Filament\Resources\Configurations\ConfigurationResource;
use VagKaefer\CmsFilament\Filament\Resources\Permissions\PermissionResource;
use VagKaefer\CmsFilament\Filament\Resources\Roles\RoleResource;
use VagKaefer\CmsFilament\Filament\Resources\Users\UserResource;
use VagKaefer\CmsFilament\Tests\TestCase;

/**
 * O package se integra ao painel do projeto como plugin: é assim que ele
 * acrescenta telas sem tomar para si o painel, o id, o path e as cores.
 */
class PluginRegistersComponentsTest extends TestCase
{
    private function panelWith(CmsFilamentPlugin $plugin): Panel
    {
        return Panel::make()
            ->id('admin-' . uniqid())
            ->path('painel')
            ->plugin($plugin);
    }

    public function testRegistersTheAdministrativeResources(): void
    {
        $panel = $this->panelWith(CmsFilamentPlugin::make());

        $resources = $panel->getResources();

        foreach (
            [
            UserResource::class,
            RoleResource::class,
            PermissionResource::class,
            AuditResource::class,
            ConfigurationResource::class,
            ] as $resource
        ) {
            $this->assertContains($resource, $resources);
        }
    }

    public function testEnablesNativeMultiFactorAuthenticationWithProfilePage(): void
    {
        $panel = $this->panelWith(CmsFilamentPlugin::make());

        $providers = $panel->getMultiFactorAuthenticationProviders();

        $this->assertCount(2, $providers);
        $this->assertInstanceOf(AppAuthentication::class, $providers['app']);
        $this->assertInstanceOf(EmailAuthentication::class, $providers['email_code']);

        // Sem página de perfil não haveria onde cadastrar o segundo fator.
        $this->assertTrue($panel->hasProfile());
    }

    public function testEachComponentCanBeTurnedOff(): void
    {
        $panel = $this->panelWith(
            CmsFilamentPlugin::make()
                ->backups(false)
                ->logViewer(false)
                ->environmentIndicator(false)
                ->multiFactorAuthentication(false)
        );

        $this->assertSame([], $panel->getMultiFactorAuthenticationProviders());
        $this->assertFalse($panel->hasPlugin('filament-spatie-backup'));
        $this->assertFalse($panel->hasPlugin('achyutn/filament-log-viewer'));
    }

    public function testNavigationGroupIsConfigurable(): void
    {
        $this->panelWith(CmsFilamentPlugin::make()->navigationGroup('Sistema'));

        $this->assertSame('Sistema', config('cms-filament.navigation_group'));
        $this->assertSame('Sistema', UserResource::getNavigationGroup());
    }

    public function testUserExportIsOptInAndOffByDefault(): void
    {
        $this->assertFalse(CmsFilamentPlugin::make()->hasUserExport());
        $this->assertTrue(CmsFilamentPlugin::make()->userExport()->hasUserExport());
        $this->assertFalse(UserResource::userExportEnabled());
    }
}
