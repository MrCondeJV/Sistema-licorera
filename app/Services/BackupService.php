<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Category;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\SubscriptionPayment;
use App\Models\Supplier;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BackupService
{
    protected string $backupDir;
    protected string $configFile;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        $this->configFile = storage_path('app/backup_schedule.json');

        if (!File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Get auto-backup schedule configuration.
     */
    public function getScheduleConfig(): array
    {
        if (File::exists($this->configFile)) {
            try {
                $content = File::get($this->configFile);
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    return array_merge($this->defaultConfig(), $decoded);
                }
            } catch (Exception $e) {
                // Return default on error
            }
        }

        return $this->defaultConfig();
    }

    /**
     * Default configuration.
     */
    protected function defaultConfig(): array
    {
        return [
            'enabled' => true,
            'frequency' => 'daily', // daily, weekly, twice_daily
            'time' => '02:00',
            'type' => 'all', // all (general + tenants), general_only
            'retention_days' => 30,
            'last_run_at' => null,
            'last_status' => null,
            'last_message' => 'Sin ejecuciones automáticas previas.',
        ];
    }

    /**
     * Update auto-backup schedule configuration.
     */
    public function saveScheduleConfig(array $data): array
    {
        $current = $this->getScheduleConfig();

        $updated = [
            'enabled' => filter_var($data['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'frequency' => in_array($data['frequency'] ?? '', ['daily', 'weekly', 'twice_daily']) ? $data['frequency'] : 'daily',
            'time' => !empty($data['time']) ? $data['time'] : '02:00',
            'type' => in_array($data['type'] ?? '', ['all', 'general_only']) ? $data['type'] : 'all',
            'retention_days' => max(1, min(365, (int) ($data['retention_days'] ?? 30))),
            'last_run_at' => $current['last_run_at'],
            'last_status' => $current['last_status'],
            'last_message' => $current['last_message'],
        ];

        File::put($this->configFile, json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        AuditService::log(
            'backup_schedule_updated',
            'System',
            0,
            $current,
            $updated,
            "Actualización de cron de auto-copias de seguridad programadas"
        );

        return $updated;
    }

    /**
     * Run the automated scheduled backup workflow.
     */
    public function runScheduledBackup(): array
    {
        $config = $this->getScheduleConfig();

        if (!$config['enabled']) {
            return [
                'success' => false,
                'message' => 'Las auto-copias de seguridad están deshabilitadas en la configuración.',
            ];
        }

        $generated = [];
        $now = now();
        $timestamp = $now->format('Y-m-d_His');

        try {
            // 1. Generate full platform SQLite snapshot
            $sqliteFilename = "backup_auto_general_{$timestamp}.sqlite";
            $this->createGeneralSqliteBackup($sqliteFilename);
            $generated[] = $sqliteFilename;

            // 2. If 'all' type, also generate JSON backups for all active tenants
            if ($config['type'] === 'all') {
                $tenants = Tenant::where('status', 'active')->get();
                foreach ($tenants as $tenant) {
                    $slug = Str::slug($tenant->name);
                    $tenantFilename = "backup_auto_tenant_{$tenant->id}_{$slug}_{$timestamp}.json";
                    $this->createTenantJsonBackup($tenant, $tenantFilename);
                    $generated[] = $tenantFilename;
                }
            }

            // 3. Prune old backups according to retention policy
            $prunedCount = $this->pruneOldBackups($config['retention_days']);

            // 4. Update schedule config record
            $config['last_run_at'] = $now->format('Y-m-d H:i:s');
            $config['last_status'] = 'success';
            $config['last_message'] = "Generadas " . count($generated) . " copias automáticas exitosamente. ({$prunedCount} archivos antiguos depurados).";
            File::put($this->configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            AuditService::log(
                'backup_auto_executed',
                'System',
                0,
                null,
                [
                    'generated_files' => $generated,
                    'pruned_count' => $prunedCount,
                ],
                "Ejecución exitosa de auto-copia de seguridad programada"
            );

            return [
                'success' => true,
                'message' => $config['last_message'],
                'files' => $generated,
                'pruned' => $prunedCount,
            ];
        } catch (Exception $e) {
            $config['last_run_at'] = $now->format('Y-m-d H:i:s');
            $config['last_status'] = 'error';
            $config['last_message'] = "Error al ejecutar auto-copia: " . $e->getMessage();
            File::put($this->configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return [
                'success' => false,
                'message' => $config['last_message'],
            ];
        }
    }

    /**
     * Create general SQLite database snapshot.
     */
    public function createGeneralSqliteBackup(string $filename): string
    {
        $dbPath = database_path('database.sqlite');
        $targetPath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        if (File::exists($dbPath)) {
            File::copy($dbPath, $targetPath);
        } else {
            File::put($targetPath, "-- Respaldo SQLite generado a las " . now());
        }

        return $targetPath;
    }

    /**
     * Create isolated JSON backup for a specific tenant.
     */
    public function createTenantJsonBackup(Tenant $tenant, string $filename): string
    {
        $targetPath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        $data = [
            'metadata' => [
                'system' => 'Licorera POS SaaS',
                'version' => '2.0',
                'backup_type' => 'specific_tenant',
                'tenant_id' => $tenant->id,
                'tenant_uuid' => $tenant->uuid,
                'tenant_name' => $tenant->name,
                'tenant_slug' => $tenant->slug,
                'created_at' => now()->toIso8601String(),
            ],
            'tenant' => $tenant->toArray(),
            'branches' => Branch::where('tenant_id', $tenant->id)->get()->toArray(),
            'categories' => Category::where('tenant_id', $tenant->id)->get()->toArray(),
            'products' => Product::where('tenant_id', $tenant->id)->with('presentations')->get()->toArray(),
            'customers' => Customer::where('tenant_id', $tenant->id)->with('debts')->get()->toArray(),
            'suppliers' => Supplier::where('tenant_id', $tenant->id)->get()->toArray(),
            'tables' => Table::where('tenant_id', $tenant->id)->get()->toArray(),
            'promotions' => Promotion::where('tenant_id', $tenant->id)->with('products')->get()->toArray(),
            'sales' => Sale::where('tenant_id', $tenant->id)->with(['items', 'payments'])->limit(5000)->get()->toArray(),
            'purchases' => Purchase::where('tenant_id', $tenant->id)->with('items')->limit(2000)->get()->toArray(),
            'expenses' => Expense::where('tenant_id', $tenant->id)->limit(2000)->get()->toArray(),
            'cash_sessions' => CashSession::where('tenant_id', $tenant->id)->with('movements')->limit(500)->get()->toArray(),
            'settings' => Setting::where('tenant_id', $tenant->id)->get()->toArray(),
        ];

        File::put($targetPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $targetPath;
    }

    /**
     * Prune backups older than retention days.
     */
    public function pruneOldBackups(int $retentionDays): int
    {
        $files = File::files($this->backupDir);
        $cutoff = now()->subDays($retentionDays)->timestamp;
        $deleted = 0;

        foreach ($files as $file) {
            if ($file->getMTime() < $cutoff) {
                try {
                    File::delete($file->getRealPath());
                    $deleted++;
                } catch (Exception $e) {
                    // Ignore deletion error on locked file
                }
            }
        }

        return $deleted;
    }
}
