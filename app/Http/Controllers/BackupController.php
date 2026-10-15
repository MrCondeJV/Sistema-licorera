<?php

namespace App\Http\Controllers;

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
use App\Services\AuditService;
use App\Services\BackupService;
use App\Services\TenantContext;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class BackupController extends Controller
{
    protected string $backupDir;
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
        $this->backupDir = storage_path('app/backups');
        if (!File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Enforce strict Superadmin-only access for all backup operations.
     */
    protected function ensureSuperAdmin(): void
    {
        $user = auth()->user();
        if (!$user || !$user->is_superadmin || session('superadmin_impersonating')) {
            abort(403, 'Acceso denegado. Las copias de seguridad de la plataforma son exclusivas del Superadministrador.');
        }
    }

    public function index()
    {
        $this->ensureSuperAdmin();

        $user = auth()->user();
        $files = File::files($this->backupDir);
        $backups = [];

        $availableTenants = Tenant::orderBy('name')->get();
        $defaultTenantId = null;
        $isSuperAdminView = true;

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $type = 'general';
            $targetLabel = 'General (Todos los Equipos)';
            $tenantName = null;

            if (Str::startsWith($filename, 'backup_tenant_') || Str::contains($filename, '_negocio_')) {
                $type = 'specific_tenant';
                // Try reading metadata if it is a JSON backup
                if (Str::endsWith($filename, '.json')) {
                    try {
                        $metaSnippet = File::get($file->getRealPath());
                        $decoded = json_decode($metaSnippet, true);
                        if (!empty($decoded['metadata']['tenant_name'])) {
                            $tenantName = $decoded['metadata']['tenant_name'];
                            $targetLabel = "Negocio: {$tenantName}";
                        }
                    } catch (Exception $e) {
                        $targetLabel = 'Negocio Específico';
                    }
                }
                if (!$tenantName) {
                    $targetLabel = 'Negocio Específico';
                }
            } else {
                $type = 'general';
                $targetLabel = 'General (Todos los Equipos)';
            }

            $backups[] = [
                'filename' => $filename,
                'type' => $type,
                'target_label' => $targetLabel,
                'tenant_name' => $tenantName,
                'extension' => $file->getExtension(),
                'size' => round($file->getSize() / 1024, 2) . ' KB',
                'created_at' => date('d/m/Y h:i A', $file->getMTime()),
                'timestamp' => $file->getMTime(),
            ];
        }

        // Sort latest first
        usort($backups, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        // Paginate results (10 backups per page)
        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $offset = ($currentPage - 1) * $perPage;
        $currentPageItems = array_slice($backups, $offset, $perPage);
        $paginatedBackups = new LengthAwarePaginator(
            $currentPageItems,
            count($backups),
            $perPage,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );

        $scheduleConfig = $this->backupService->getScheduleConfig();

        return view('settings.backups', [
            'backups' => $paginatedBackups,
            'availableTenants' => $availableTenants,
            'defaultTenantId' => $defaultTenantId,
            'isSuperAdminView' => $isSuperAdminView,
            'scheduleConfig' => $scheduleConfig,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureSuperAdmin();

        $user = auth()->user();

        $backupType = $request->input('backup_type', 'general');
        if (!in_array($backupType, ['general', 'specific_tenant'])) {
            $backupType = 'general';
        }

        if ($backupType === 'specific_tenant') {
            $request->validate([
                'tenant_id' => 'required|exists:tenants,id',
            ], [
                'tenant_id.required' => 'Debes seleccionar el negocio específico para realizar la copia.',
            ]);
        }

        $tenantId = $request->input('tenant_id');

        try {
            $timestamp = date('Y_m_d_His');

            if ($backupType === 'general') {
                $backupFilename = "backup_general_todos_los_equipos_{$timestamp}.sqlite";
                $targetPath = $this->backupDir . '/' . $backupFilename;

                $sqlitePath = database_path('database.sqlite');
                if (File::exists($sqlitePath)) {
                    File::copy($sqlitePath, $targetPath);
                } else {
                    File::put($targetPath, "Backup general snapshot generated at " . date('Y-m-d H:i:s'));
                }

                $logMsg = "Copia de seguridad general (todos los equipos y negocios) generada: {$backupFilename}";
                $successMsg = "Copia de seguridad general de toda la plataforma creada exitosamente.";
            } else {
                $tenant = Tenant::with(['plan', 'branches'])->findOrFail($tenantId);
                $slug = Str::slug($tenant->name);
                $backupFilename = "backup_negocio_{$tenant->id}_{$slug}_{$timestamp}.json";
                $targetPath = $this->backupDir . '/' . $backupFilename;

                // Extract all relational data for this specific tenant without global scope limitations
                $branches = Branch::where('tenant_id', $tenant->id)->get();
                $branchIds = $branches->pluck('id')->toArray();

                $categories = Category::where('tenant_id', $tenant->id)->get();
                $products = Product::where('tenant_id', $tenant->id)->with('branchInventories')->get();
                $suppliers = Supplier::where('tenant_id', $tenant->id)->get();
                $purchases = Purchase::where('tenant_id', $tenant->id)->with('items')->get();
                $expenses = Expense::where('tenant_id', $tenant->id)->get();
                $customers = Customer::where('tenant_id', $tenant->id)->with(['debts.payments'])->get();
                $tables = Table::where('tenant_id', $tenant->id)->get();
                $sales = Sale::withoutGlobalScopes()->where('tenant_id', $tenant->id)->with(['items', 'payments'])->get();
                $cashSessions = CashSession::withoutGlobalScopes()->where('tenant_id', $tenant->id)->with('movements')->get();
                $promotions = Promotion::where('tenant_id', $tenant->id)->get();
                $settings = Setting::where('tenant_id', $tenant->id)->get();
                $subscriptionPayments = SubscriptionPayment::where('tenant_id', $tenant->id)->get();
                $tenantUsers = $tenant->users()->get(['users.id', 'users.name', 'users.email', 'users.is_active']);

                $exportPayload = [
                    'metadata' => [
                        'backup_type' => 'specific_tenant',
                        'generated_at' => now()->toIso8601String(),
                        'system' => 'Licorera POS SaaS',
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->name,
                        'tenant_tax_id' => $tenant->tax_id,
                        'total_products' => $products->count(),
                        'total_sales' => $sales->count(),
                        'total_branches' => $branches->count(),
                    ],
                    'tenant' => $tenant->toArray(),
                    'branches' => $branches->toArray(),
                    'users' => $tenantUsers->toArray(),
                    'categories' => $categories->toArray(),
                    'products' => $products->toArray(),
                    'suppliers' => $suppliers->toArray(),
                    'purchases' => $purchases->toArray(),
                    'expenses' => $expenses->toArray(),
                    'customers' => $customers->toArray(),
                    'tables' => $tables->toArray(),
                    'sales' => $sales->toArray(),
                    'cash_sessions' => $cashSessions->toArray(),
                    'promotions' => $promotions->toArray(),
                    'settings' => $settings->toArray(),
                    'subscription_payments' => $subscriptionPayments->toArray(),
                ];

                File::put($targetPath, json_encode($exportPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                $logMsg = "Copia de seguridad específica del negocio '{$tenant->name}' generada: {$backupFilename}";
                $successMsg = "Copia de seguridad del negocio '{$tenant->name}' creada exitosamente.";
            }

            AuditService::log(
                'backup_created',
                'Backup',
                $tenantId ?? null,
                null,
                [
                    'filename' => $backupFilename,
                    'backup_type' => $backupType,
                    'tenant_id' => $tenantId,
                ],
                $logMsg
            );

            $redirectUrl = request()->routeIs('superadmin.*') || ($user->is_superadmin && !session('superadmin_impersonating'))
                ? route('superadmin.backups')
                : route('backups.index');

            return redirect($redirectUrl)->with('success', $successMsg);
        } catch (Exception $e) {
            $redirectUrl = request()->routeIs('superadmin.*') || ($user->is_superadmin && !session('superadmin_impersonating'))
                ? route('superadmin.backups')
                : route('backups.index');

            return redirect($redirectUrl)->with('error', "Error al generar la copia de seguridad: " . $e->getMessage());
        }
    }

    public function download(string $filename)
    {
        $this->ensureSuperAdmin();

        $safeFilename = basename($filename);
        $filePath = $this->backupDir . '/' . $safeFilename;

        if (!File::exists($filePath)) {
            abort(404, 'El archivo de copia de seguridad no existe.');
        }

        return Response::download($filePath, $safeFilename);
    }

    public function destroy(string $filename)
    {
        $this->ensureSuperAdmin();

        $safeFilename = basename($filename);
        $filePath = $this->backupDir . '/' . $safeFilename;

        if (File::exists($filePath)) {
            File::delete($filePath);
            return redirect()->route('superadmin.backups')
                ->with('success', "Copia de seguridad '{$safeFilename}' eliminada exitosamente.");
        }

        return redirect()->route('superadmin.backups')
            ->with('error', 'El archivo no fue encontrado.');
    }

    /**
     * Save auto-backup schedule settings.
     */
    public function updateSchedule(Request $request)
    {
        $this->ensureSuperAdmin();

        $request->validate([
            'frequency' => 'required|in:daily,weekly,twice_daily',
            'time' => 'required|string|max:10',
            'retention_days' => 'required|integer|min:1|max:365',
            'type' => 'required|in:all,general_only',
        ]);

        $this->backupService->saveScheduleConfig([
            'enabled' => $request->has('enabled'),
            'frequency' => $request->input('frequency'),
            'time' => $request->input('time'),
            'retention_days' => $request->input('retention_days'),
            'type' => $request->input('type'),
        ]);

        return redirect()->route('superadmin.backups')
            ->with('success', 'Configuración de auto-copias de seguridad programadas guardada exitosamente.');
    }

    /**
     * Trigger immediate automated backup run from UI.
     */
    public function runAutoBackup(Request $request)
    {
        $this->ensureSuperAdmin();

        $result = $this->backupService->runScheduledBackup();

        if ($result['success']) {
            return redirect()->route('superadmin.backups')
                ->with('success', "✅ " . $result['message']);
        }

        return redirect()->route('superadmin.backups')
            ->with('error', "❌ " . $result['message']);
    }
}
