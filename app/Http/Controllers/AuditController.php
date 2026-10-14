<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = app(TenantContext::class)->getTenantId()
            ?? session('active_tenant_id')
            ?? auth()->user()?->tenantUsers()->where('is_active', true)->first()?->tenant_id;

        if (!$tenantId && !auth()->user()->is_superadmin) {
            abort(403, 'No tienes un negocio activo asignado.');
        }

        // Fetch ONLY users of this specific tenant
        $tenantUsers = User::whereHas('tenantUsers', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->with(['tenantUsers' => function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        }])->orderBy('name')->get();

        $query = AuditLog::where('tenant_id', $tenantId)
            ->with(['user.tenantUsers' => function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            }])
            ->latest('created_at');

        // STRICT PRIVACY ISOLATION:
        // Regular administrators MUST NOT see superadmin events or actions performed by superadmin
        if (!auth()->user()->is_superadmin) {
            $query->where('action', 'not like', 'superadmin_%')
                  ->where(function ($q) {
                      $q->whereNull('user_id')
                        ->orWhereHas('user', function ($uq) {
                            $uq->where('is_superadmin', false);
                        });
                  });
        }

        // Filter: User / Employee
        if ($request->filled('user_id')) {
            $targetUserId = $request->input('user_id');
            if (auth()->user()->is_superadmin || $tenantUsers->contains('id', $targetUserId)) {
                $query->where('user_id', $targetUserId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Filter: Module / Category
        if ($request->filled('category')) {
            $category = $request->input('category');
            $actionMap = [
                'inventory' => ['product_created', 'product_updated', 'stock_adjusted', 'inventory_count_applied'],
                'sales' => ['sale_created', 'sale_refunded', 'customer_debt_charged'],
                'cash' => ['cash_session_opened', 'cash_session_closed', 'cash_movement_in', 'cash_movement_out'],
                'tables' => ['account_opened', 'table_item_added', 'table_item_removed', 'account_transferred', 'account_cancelled'],
                'expenses' => ['expense_created'],
                'purchases' => ['purchase_created', 'purchase_received', 'supplier_created', 'supplier_quick_created', 'supplier_updated', 'supplier_status_toggled'],
                'customers' => ['customer_created', 'debt_created', 'debt_payment_received'],
                'security' => ['user_created', 'user_password_reset', 'user_pin_reset', 'user_status_toggled', 'settings_updated'],
            ];

            if (isset($actionMap[$category])) {
                $query->whereIn('action', $actionMap[$category]);
            }
        }

        // Filter: Specific Action
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        // Filter: Date Presets & Custom Ranges
        if ($request->filled('preset')) {
            $preset = $request->input('preset');
            if ($preset === 'today') {
                $query->where('created_at', '>=', Carbon::today()->startOfDay());
            } elseif ($preset === 'yesterday') {
                $query->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);
            } elseif ($preset === 'this_week') {
                $query->where('created_at', '>=', Carbon::now()->startOfWeek());
            } elseif ($preset === 'this_month') {
                $query->where('created_at', '>=', Carbon::now()->startOfMonth());
            }
        } else {
            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', Carbon::parse($request->input('date_from'))->startOfDay());
            }
            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', Carbon::parse($request->input('date_to'))->endOfDay());
            }
        }

        // Filter: Free Text Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // KPI Summary Metrics
        $todayStart = Carbon::today()->startOfDay();
        $totalToday = (clone $query)->where('created_at', '>=', $todayStart)->count();

        // Count cashier operations today
        $cashierUserIds = $tenantUsers->filter(function ($u) use ($tenantId) {
            return $u->hasRoleInTenant($tenantId, 'cashier');
        })->pluck('id');
        $cashierActionsToday = (clone $query)
            ->where('created_at', '>=', $todayStart)
            ->whereIn('user_id', $cashierUserIds)
            ->count();

        // Inventory changes count
        $inventoryActionsCount = (clone $query)
            ->whereIn('action', ['product_created', 'product_updated', 'stock_adjusted', 'inventory_count_applied'])
            ->count();

        // Cash / Expense actions count
        $cashAndExpenseCount = (clone $query)
            ->whereIn('action', ['cash_session_opened', 'cash_session_closed', 'cash_movement_in', 'cash_movement_out', 'expense_created'])
            ->count();

        $logs = $query->paginate(25)->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'tenantUsers' => $tenantUsers,
            'metrics' => [
                'totalToday' => $totalToday,
                'cashierActionsToday' => $cashierActionsToday,
                'inventoryActions' => $inventoryActionsCount,
                'cashAndExpense' => $cashAndExpenseCount,
            ],
            'currentTenantId' => $tenantId,
        ]);
    }
}
