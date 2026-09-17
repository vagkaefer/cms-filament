<?php

namespace VagKaefer\CmsFilament\Console\Commands;

use Illuminate\Console\Command;

class CleanLargeLogFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:clean {--size= : Tamanho máximo do arquivo em MB}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean log file when it exceeds the specified size';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $logFile = storage_path('logs/laravel.log');
        $maxSizeMB = (int) ($this->option('size') ?? config('cms-filament.logs.max_size_mb', 5));
        $maxSizeBytes = $maxSizeMB * 1024 * 1024;

        if (! file_exists($logFile)) {
            $this->info('Log file does not exist.');

            return 0;
        }

        $fileSize = filesize($logFile);
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);

        $this->info("Current log file size: {$fileSizeMB} MB");

        if ($fileSize > $maxSizeBytes) {
            $this->info("Log file exceeds {$maxSizeMB} MB. Cleaning...");

            // Opção 1: Limpar completamente o arquivo
            // file_put_contents($logFile, '');

            // Opção 2: Manter as últimas 1000 linhas
            $lines = file($logFile);
            $totalLines = count($lines);
            $linesToKeep = 1000;

            if ($totalLines > $linesToKeep) {
                $keptLines = array_slice($lines, -$linesToKeep);
                file_put_contents($logFile, implode('', $keptLines));
                $this->info("Kept last {$linesToKeep} lines. Removed " . ($totalLines - $linesToKeep) . ' lines.');
            } else {
                file_put_contents($logFile, '');
                $this->info('File cleared completely.');
            }

            $newSize = round(filesize($logFile) / 1024 / 1024, 2);
            $this->info("New log file size: {$newSize} MB");
        } else {
            $this->info('Log file is within the size limit. No action needed.');
        }

        return 0;
    }
}
