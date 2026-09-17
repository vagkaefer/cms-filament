<?php

namespace VagKaefer\CmsFilament\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clean {--hours= : Número de horas para considerar log como antigo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deleta arquivos de log com mais de X horas (padrão: 6 horas)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) ($this->option('hours') ?? config('cms-filament.logs.clean_hours', 6));
        $this->info("Procurando logs com mais de {$hours} horas...");

        $logsPath = storage_path('logs');

        if (! File::exists($logsPath)) {
            $this->error("Diretório de logs não encontrado: {$logsPath}");

            return 1;
        }

        $files = File::files($logsPath);
        $cutoffTime = Carbon::now()->subHours($hours);
        $deletedCount = 0;
        $totalSize = 0;

        foreach ($files as $file) {
            $lastModified = Carbon::createFromTimestamp($file->getMTime());

            // Verifica se o arquivo é mais antigo que o tempo limite
            if ($lastModified->lessThan($cutoffTime)) {
                $fileSize = $file->getSize();
                $fileName = $file->getFilename();

                // Não deleta o arquivo de log do dia atual
                if ($fileName === 'laravel-' . date('Y-m-d') . '.log') {
                    $this->warn("Mantendo log do dia atual: {$fileName}");

                    continue;
                }

                try {
                    File::delete($file->getPathname());
                    $deletedCount++;
                    $totalSize += $fileSize;
                    $this->line("✓ Deletado: {$fileName} ({$this->formatBytes($fileSize)})");
                } catch (\Exception $e) {
                    $this->error("✗ Erro ao deletar {$fileName}: " . $e->getMessage());
                }
            }
        }

        if ($deletedCount > 0) {
            $this->info("\n✓ {$deletedCount} arquivo(s) deletado(s), liberando " . $this->formatBytes($totalSize));
        } else {
            $this->info("\n✓ Nenhum log antigo encontrado para deletar.");
        }

        return 0;
    }

    /**
     * Formata bytes para formato legível
     */
    private function formatBytes(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $powInt = (int) $pow;
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$powInt];
    }
}
