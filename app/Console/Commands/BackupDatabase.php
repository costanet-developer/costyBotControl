<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep=7 : Número de backups a conservar}';
    protected $description = 'Realiza backup de PostgreSQL usando pg_dump';

    public function handle(): int
    {
        $db = config('database.connections.pgsql');
        $path = storage_path("backups");
        $file = "costy_sesiones_" . now()->format('Ymd_His') . ".sql";

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s -d %s --no-owner --no-acl > %s/%s 2>&1',
            escapeshellarg($db['password']),
            escapeshellarg($db['host']),
            escapeshellarg($db['port']),
            escapeshellarg($db['username']),
            escapeshellarg($db['database']),
            escapeshellarg($path),
            escapeshellarg($file)
        );

        $this->info("Ejecutando pg_dump...");
        $output = shell_exec($command);
        $fullPath = "$path/$file";

        if (!file_exists($fullPath) || filesize($fullPath) === 0) {
            $this->error("Error en pg_dump: " . ($output ?? 'sin salida'));
            return self::FAILURE;
        }

        $this->info("Backup creado: {$file} (" . number_format(filesize($fullPath) / 1024 / 1024, 2) . " MB)");
        $this->limpiarViejos($path, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    protected function limpiarViejos(string $path, int $keep): void
    {
        $files = glob("{$path}/costy_sesiones_*.sql");
        if (count($files) <= $keep) return;

        usort($files, fn($a, $b) => filemtime($a) - filemtime($b));
        $toDelete = array_slice($files, 0, count($files) - $keep);

        foreach ($toDelete as $file) {
            unlink($file);
            $this->warn("Backup antiguo eliminado: " . basename($file));
        }
    }
}
