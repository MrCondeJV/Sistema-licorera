<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class AutoBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:auto {--force : Force execution even if disabled in config}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta la auto-copia de seguridad programada de la plataforma SaaS y depura respaldos antiguos';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $this->info("Iniciando proceso de auto-copia de seguridad...");

        $config = $backupService->getScheduleConfig();

        if (!$config['enabled'] && !$this->option('force')) {
            $this->warn("Las auto-copias de seguridad están deshabilitadas en la configuración. Usa --force para forzar.");
            return self::SUCCESS;
        }

        $result = $backupService->runScheduledBackup();

        if ($result['success']) {
            $this->info("✅ " . $result['message']);
            if (!empty($result['files'])) {
                foreach ($result['files'] as $f) {
                    $this->line("   - Generado: {$f}");
                }
            }
            return self::SUCCESS;
        } else {
            $this->error("❌ Error: " . $result['message']);
            return self::FAILURE;
        }
    }
}
