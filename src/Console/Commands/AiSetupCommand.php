<?php

namespace VagKaefer\CmsFilament\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Garante que o CLAUDE.md do projeto consumidor importe o guia de IA do
 * cms-filament via a sintaxe de @import do Claude Code. O guia vive em
 * `vendor/vagkaefer/cms-filament/.ai/consumer-guide.md`, então o @import lê sempre
 * a versão instalada no vendor — o guia se auto-atualiza a cada
 * `composer update` sem precisar re-publicar.
 *
 * O comando é idempotente: cria o CLAUDE.md se não existir, injeta a linha
 * de import se faltar e não faz nada se já estiver presente. Depende apenas
 * de File e base_path() — nunca de classes do App do consumidor — para poder
 * rodar em qualquer projeto, inclusive recém-clonado.
 */
class AiSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms-filament:ai-setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Registra o guia de IA do cms-filament no CLAUDE.md do projeto (idempotente)';

    /**
     * Linha de @import (relativa ao CLAUDE.md do consumidor) que carrega o guia.
     */
    protected const IMPORT_LINE = '@./vendor/vagkaefer/cms-filament/.ai/consumer-guide.md';

    /**
     * Marker de âncora para tornar a idempotência robusta a edições manuais.
     */
    protected const MARKER = '<!-- cms-filament:ai-guide -->';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = $this->claudeMdPath();

        if (! File::exists($path)) {
            File::put($path, $this->buildBlock());
            $this->info("CLAUDE.md criado com o guia de IA do cms-filament: {$path}");

            return self::SUCCESS;
        }

        $contents = File::get($path);

        if ($this->alreadyImported($contents)) {
            $this->info('CLAUDE.md já importa o guia de IA do cms-filament. Nada a fazer.');

            return self::SUCCESS;
        }

        $separator = str_ends_with($contents, "\n") ? "\n" : "\n\n";
        File::append($path, $separator . $this->buildBlock());
        $this->info('Guia de IA do cms-filament adicionado ao CLAUDE.md existente.');

        return self::SUCCESS;
    }

    /**
     * Caminho absoluto do CLAUDE.md do projeto consumidor.
     */
    protected function claudeMdPath(): string
    {
        return base_path('CLAUDE.md');
    }

    /**
     * Bloco (marker + import) inserido no CLAUDE.md.
     */
    protected function buildBlock(): string
    {
        return self::MARKER . "\n" . self::IMPORT_LINE . "\n";
    }

    /**
     * Indica se o CLAUDE.md já referencia o guia (por marker ou pela linha).
     */
    protected function alreadyImported(string $contents): bool
    {
        return str_contains($contents, self::MARKER)
            || str_contains($contents, self::IMPORT_LINE);
    }
}
