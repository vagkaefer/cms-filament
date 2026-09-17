<?php

namespace VagKaefer\CmsFilament\Tests\Unit;

use Composer\InstalledVersions;
use VagKaefer\CmsFilament\Tests\TestCase;

/**
 * Garante que a versão exibida no painel é a do package vagkaefer/cms-filament
 * instalado, e não a versão da aplicação consumidora.
 *
 * Antes desta correção o widget lia config('app.version'), que cai em
 * app_version() — baseada nas tags Git / composer.json do projeto
 * consumidor. Em um projeto sem tags e sem chave "version" isso resultava
 * no literal "0.0.0" na tela inicial do gestor.
 */
class CmsFilamentVersionTest extends TestCase
{
    public function testRetornaAVersaoDoPackageInstaladoPeloComposer(): void
    {
        if (! InstalledVersions::isInstalled('vagkaefer/cms-filament')) {
            $this->markTestSkipped('vagkaefer/cms-filament não está instalado como package Composer neste contexto.');
        }

        $this->assertSame(
            InstalledVersions::getPrettyVersion('vagkaefer/cms-filament'),
            cms_filament_version(),
        );
    }

    public function testNuncaRetornaOPlaceholderZerado(): void
    {
        $this->assertNotSame('0.0.0', cms_filament_version());
    }

    public function testConfigCmsVersionUsaAVersaoDoPackage(): void
    {
        $this->assertSame(cms_filament_version(), config('cms-filament.version'));
    }
}
