<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Services\AuditService;
use App\Services\InventoryService;
use App\Services\TenantContext;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * List all branches of the current tenant with status, limits, and metrics.
     */
    public function index()
    {
        $tenant = app(TenantContext::class)->getTenant();
        $branches = Branch::where('tenant_id', $tenant->id)
            ->withCount(['users', 'cashRegisters', 'tables'])
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->get();

        $maxBranches = $tenant->getMaxBranches();
        $currentCount = $branches->count();
        $canCreate = $tenant->canCreateBranch();

        $transfers = StockTransfer::where('tenant_id', $tenant->id)
            ->with(['fromBranch', 'toBranch', 'user', 'items.product'])
            ->latest('id')
            ->take(15)
            ->get();

        $products = Product::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('branches.index', [
            'tenant' => $tenant,
            'branches' => $branches,
            'maxBranches' => $maxBranches,
            'currentCount' => $currentCount,
            'canCreate' => $canCreate,
            'transfers' => $transfers,
            'products' => $products,
        ]);
    }

    /**
     * Store a new branch respecting plan limit and race-condition locking.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
        ], [
            'name.required' => 'El Nombre de la Sucursal es obligatorio.',
            'name.max' => 'El nombre no puede superar los 150 caracteres.',
        ]);

        $tenantId = app(TenantContext::class)->getTenantId();

        try {
            $branch = DB::transaction(function () use ($tenantId, $validated) {
                // Lock tenant to prevent concurrent bypass of max_branches
                $tenant = \App\Models\Tenant::where('id', $tenantId)->lockForUpdate()->firstOrFail();

                $max = $tenant->getMaxBranches();
                $current = Branch::where('tenant_id', $tenant->id)->count();

                if ($max !== null && $current >= $max) {
                    throw new Exception("Has alcanzado el límite de sucursales permitido por tu plan ({$max} sucursal/es). Actualiza tu plan en Facturación y Wompi para agregar más sucursales.");
                }

                $code = !empty($validated['code']) 
                    ? strtoupper(trim($validated['code'])) 
                    : strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 4));

                $branch = Branch::create([
                    'tenant_id' => $tenant->id,
                    'name' => $validated['name'],
                    'code' => $code,
                    'address' => $validated['address'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'is_main' => false,
                    'is_active' => true,
                ]);

                // Create initial cash register for the branch
                \App\Models\CashRegister::create([
                    'tenant_id' => $tenant->id,
                    'branch_id' => $branch->id,
                    'name' => 'Caja 01 - ' . $branch->name,
                    'is_active' => true,
                ]);

                // Assign the admin user to the new branch
                if (auth()->check()) {
                    $branch->users()->attach(auth()->id(), ['tenant_id' => $tenant->id]);
                }

                // Propagate existing tenant products to branch_inventories with 0 stock
                $products = Product::where('tenant_id', $tenant->id)->get();
                foreach ($products as $p) {
                    \App\Models\BranchInventory::create([
                        'tenant_id' => $tenant->id,
                        'branch_id' => $branch->id,
                        'product_id' => $p->id,
                        'stock' => 0,
                        'stock_min' => $p->stock_min,
                    ]);
                }

                AuditService::log(
                    'branch_created',
                    'Branch',
                    $branch->id,
                    null,
                    ['name' => $branch->name, 'code' => $branch->code],
                    "Creación de nueva sucursal '{$branch->name}'"
                );

                return $branch;
            });

            return redirect()->route('branches.index')->with('success', "Sucursal '{$branch->name}' creada exitosamente.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Update branch details.
     */
    public function update(Request $request, Branch $branch)
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        if ($branch->tenant_id !== $tenantId) {
            abort(403, 'No tienes permiso para modificar esta sucursal.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
        ]);

        $old = [
            'name' => $branch->name,
            'code' => $branch->code,
            'address' => $branch->address,
        ];

        $branch->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ? strtoupper(trim($validated['code'])) : $branch->code,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
        ]);

        AuditService::log(
            'branch_updated',
            'Branch',
            $branch->id,
            $old,
            $validated,
            "Actualización de sucursal '{$branch->name}'"
        );

        return redirect()->route('branches.index')->with('success', "Sucursal '{$branch->name}' actualizada correctamente.");
    }

    /**
     * Toggle branch active/inactive status (preserves history, never physical delete).
     */
    public function toggle(Branch $branch)
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        if ($branch->tenant_id !== $tenantId) {
            abort(403, 'No tienes permiso para modificar esta sucursal.');
        }

        if ($branch->is_main) {
            return back()->with('error', 'La Sede Principal no puede ser desactivada.');
        }

        $branch->is_active = !$branch->is_active;
        $branch->save();

        // If the deactivated branch was active in session, switch back to main branch
        if (!$branch->is_active && session('active_branch_id') === $branch->id) {
            $mainBranch = Branch::where('tenant_id', $tenantId)->where('is_main', true)->first();
            if ($mainBranch) {
                session(['active_branch_id' => $mainBranch->id]);
                app(TenantContext::class)->setBranch($mainBranch);
            }
        }

        $label = $branch->is_active ? 'activada' : 'desactivada';
        AuditService::log(
            'branch_status_toggled',
            'Branch',
            $branch->id,
            null,
            ['is_active' => $branch->is_active],
            "Sucursal '{$branch->name}' {$label}"
        );

        return back()->with('success', "Sucursal '{$branch->name}' {$label} exitosamente.");
    }

    /**
     * Store inter-branch stock transfer.
     */
    public function transfer(Request $request)
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        $request->validate([
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id|different:from_branch_id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ], [
            'to_branch_id.different' => 'La sucursal de destino debe ser diferente a la sucursal de origen.',
            'items.required' => 'Debes incluir al menos un producto en la transferencia.',
        ]);

        $fromBranch = Branch::where('tenant_id', $tenantId)->findOrFail($request->input('from_branch_id'));
        $toBranch = Branch::where('tenant_id', $tenantId)->findOrFail($request->input('to_branch_id'));

        try {
            $transfer = $this->inventoryService->transferStock(
                $fromBranch,
                $toBranch,
                $request->input('items'),
                auth()->user(),
                $request->input('notes')
            );

            return redirect()->route('branches.index')->with('success', "Transferencia #{$transfer->transfer_number} completada exitosamente.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
