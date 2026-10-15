<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateToPostgresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:migrate-to-postgres
                            {--source= : Conexión de origen (por defecto la conexión default actual)}
                            {--target=pgsql : Conexión de destino PostgreSQL configurada en database.php}
                            {--fresh : Ejecuta migrate:fresh en la base de datos destino antes de transferir datos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra automáticamente todas las tablas y datos desde SQLite/MySQL hacia PostgreSQL respetando llaves foráneas y secuencias';

    /**
     * Ordered list of tables to migrate respecting foreign key dependencies.
     *
     * @var array
     */
    protected array $tablesInOrder = [
        'plans',
        'tenants',
        'users',
        'tenant_user',
        'branches',
        'categories',
        'products',
        'product_presentations',
        'branch_inventories',
        'tables',
        'accounts',
        'account_items',
        'suppliers',
        'purchases',
        'purchase_items',
        'expense_categories',
        'expenses',
        'customers',
        'payment_methods',
        'customer_debts',
        'customer_payments',
        'cash_sessions',
        'cash_movements',
        'sales',
        'sale_items',
        'sale_payments',
        'promotions',
        'settings',
        'subscription_payments',
        'audit_logs',
        'login_attempts',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sourceConn = $this->option('source') ?: config('database.default');
        $targetConn = $this->option('target') ?: 'pgsql';

        $this->info("==========================================================");
        $this->info("   🚀 MIGRACIÓN DE BASE DE DATOS A POSTGRESQL (SaaS)     ");
        $this->info("==========================================================");
        $this->line("Conexión de origen: <fg=yellow>{$sourceConn}</>");
        $this->line("Conexión de destino: <fg=cyan>{$targetConn}</>");

        // Validate target connection exists in config
        if (!config("database.connections.{$targetConn}")) {
            $this->error("La conexión '{$targetConn}' no se encuentra definida en config/database.php.");
            return Command::FAILURE;
        }

        // Test connection to target database
        try {
            DB::connection($targetConn)->getPdo();
            $this->info("✓ Conexión exitosa al servidor PostgreSQL.");
        } catch (Exception $e) {
            $this->error("No se pudo conectar a PostgreSQL ({$targetConn}): " . $e->getMessage());
            $this->line("Verifique las credenciales en su archivo .env (DB_HOST, DB_PORT=5432, DB_DATABASE, DB_USERNAME, DB_PASSWORD).");
            return Command::FAILURE;
        }

        // Run migrations on target if --fresh option is requested
        if ($this->option('fresh')) {
            $this->warn("Ejecutando 'migrate:fresh' en la base de datos PostgreSQL de destino...");
            $exitCode = $this->call('migrate:fresh', [
                '--database' => $targetConn,
                '--force' => true,
            ]);

            if ($exitCode !== 0) {
                $this->error("Falló la ejecución de migraciones en la base de datos destino.");
                return Command::FAILURE;
            }
            $this->info("✓ Esquema de tablas creado exitosamente en PostgreSQL.");
        }

        $sourceDb = DB::connection($sourceConn);
        $targetDb = DB::connection($targetConn);

        $totalMigratedRecords = 0;

        // Temporarily disable foreign key constraints in PostgreSQL if supported
        try {
            $targetDb->statement("SET session_replication_role = 'replica';");
        } catch (Exception $e) {
            // Ignore if role does not allow
        }

        foreach ($this->tablesInOrder as $table) {
            if (!Schema::connection($sourceConn)->hasTable($table)) {
                continue;
            }
            if (!Schema::connection($targetConn)->hasTable($table)) {
                $this->warn("⚠️ La tabla '{$table}' no existe en el destino. Omitiendo.");
                continue;
            }

            $count = $sourceDb->table($table)->count();
            if ($count === 0) {
                $this->line("Tabla <fg=gray>{$table}</>: 0 registros.");
                continue;
            }

            $this->info("Migrando tabla '{$table}' ({$count} registros)...");
            $bar = $this->output->createProgressBar($count);
            $bar->start();

            // Clear table on target if not already fresh
            if (!$this->option('fresh')) {
                $targetDb->table($table)->delete();
            }

            // Transfer in chunks of 250 rows to optimize memory and speed
            $sourceDb->table($table)->orderBy('id')->chunk(250, function ($rows) use ($targetDb, $table, $bar, &$totalMigratedRecords) {
                $data = [];
                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $data[] = $rowArray;
                }

                $targetDb->table($table)->insert($data);
                $bar->advance(count($data));
                $totalMigratedRecords += count($data);
            });

            $bar->finish();
            $this->newLine();

            // Reset PostgreSQL auto-increment sequence for id column
            if (Schema::connection($targetConn)->hasColumn($table, 'id')) {
                try {
                    $targetDb->statement("
                        SELECT setval(
                            pg_get_serial_sequence('{$table}', 'id'),
                            COALESCE((SELECT MAX(id) FROM \"{$table}\"), 1)
                        );
                    ");
                } catch (Exception $e) {
                    // Sequence might not exist or be named differently, non-critical
                }
            }
        }

        // Re-enable foreign key constraints in PostgreSQL
        try {
            $targetDb->statement("SET session_replication_role = 'origin';");
        } catch (Exception $e) {
            // Ignore
        }

        $this->newLine();
        $this->info("==========================================================");
        $this->info("   🎉 MIGRACIÓN A POSTGRESQL COMPLETADA CON ÉXITO        ");
        $this->info("==========================================================");
        $this->line("Total de registros migrados: <fg=green>{$totalMigratedRecords}</>");
        $this->line("Para activar PostgreSQL permanentemente, configure en su .env:");
        $this->line("<fg=cyan>DB_CONNECTION=pgsql</>");
        $this->newLine();

        return Command::SUCCESS;
    }
}
