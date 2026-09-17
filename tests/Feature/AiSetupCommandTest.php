<?php

namespace VagKaefer\CmsFilament\Tests\Feature;

use VagKaefer\CmsFilament\Tests\TestCase;
use Illuminate\Support\Facades\File;

/**
 * Cobre o comando cms-filament:ai-setup: injeção idempotente da linha de @import do
 * guia de IA no CLAUDE.md do projeto. O base path é apontado para um diretório
 * temporário no setUp para não tocar o CLAUDE.md real do repositório.
 */
class AiSetupCommandTest extends TestCase
{
    private string $basePath;

    private const IMPORT_LINE = '@./vendor/vagkaefer/cms-filament/.ai/consumer-guide.md';

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . '/cms-filament-ai-setup-' . uniqid();
        File::makeDirectory($this->basePath, 0755, true);
        $this->app->setBasePath($this->basePath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->basePath);

        parent::tearDown();
    }

    private function claudeMd(): string
    {
        return $this->basePath . '/CLAUDE.md';
    }

    public function testCreatesClaudeMdWhenAbsent(): void
    {
        $this->assertFileDoesNotExist($this->claudeMd());

        $this->artisan('cms-filament:ai-setup')->assertSuccessful();

        $this->assertFileExists($this->claudeMd());
        $this->assertStringContainsString(self::IMPORT_LINE, File::get($this->claudeMd()));
    }

    public function testDoesNotDuplicateOnSecondRun(): void
    {
        $this->artisan('cms-filament:ai-setup')->assertSuccessful();
        $this->artisan('cms-filament:ai-setup')->assertSuccessful();

        $contents = File::get($this->claudeMd());
        $this->assertSame(1, substr_count($contents, self::IMPORT_LINE));
    }

    public function testPreservesExistingContentAndAppendsImport(): void
    {
        $existing = "# Projeto\n\nRegras específicas do projeto.\n";
        File::put($this->claudeMd(), $existing);

        $this->artisan('cms-filament:ai-setup')->assertSuccessful();

        $contents = File::get($this->claudeMd());
        $this->assertStringContainsString('Regras específicas do projeto.', $contents);
        $this->assertStringContainsString(self::IMPORT_LINE, $contents);
    }

    public function testIsNoOpWhenImportAlreadyPresent(): void
    {
        $existing = "# Projeto\n\n" . self::IMPORT_LINE . "\n";
        File::put($this->claudeMd(), $existing);

        $this->artisan('cms-filament:ai-setup')->assertSuccessful();

        $contents = File::get($this->claudeMd());
        $this->assertSame(1, substr_count($contents, self::IMPORT_LINE));
    }
}
