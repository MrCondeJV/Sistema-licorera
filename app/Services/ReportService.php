<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Metrics for executive daily dashboard with optional branch scope.
     */
    public function getDashboardMetrics(?int $branchId = null): array
    {
        $branchId = $branchId ?? app(TenantContext::class)->getBranchId() ?? session('active_branch_id');
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $todaySalesQuery = Sale::where('status', 'completed')->whereDate('sold_at', $today);
        if ($branchId) {
            $todaySalesQuery->where('branch_id', $branchId);
        }
        $todaySales = $todaySalesQuery->sum('total');

        $monthSalesQuery = Sale::where('status', 'completed')->where('sold_at', '>=', $startOfMonth);
        if ($branchId) {
            $monthSalesQuery->where('branch_id', $branchId);
        }
        $monthSales = $monthSalesQuery->sum('total');

        $todayTransactionsQuery = Sale::where('status', 'completed')->whereDate('sold_at', $today);
        if ($branchId) {
            $todayTransactionsQuery->where('branch_id', $branchId);
        }
        $todayTransactions = $todayTransactionsQuery->count();

        $averageTicket = $todayTransactions > 0 ? round($todaySales / $todayTransactions, 2) : 0;

        $tablesQuery = Table::query();
        if ($branchId) {
            $tablesQuery->where('branch_id', $branchId);
        }
        $occupiedTables = (clone $tablesQuery)->where('status', 'occupied')->count();
        $totalTables = $tablesQuery->count();

        $lowStockCount = Product::where('is_active', true)
            ->whereColumn('stock', '<=', 'stock_min')
            ->where('stock', '>', 0)
            ->count();

        $outOfStockCount = Product::where('is_active', true)
            ->where('stock', '<=', 0)
            ->count();

        $activeCashSessionsQuery = CashSession::where('status', 'open');
        if ($branchId) {
            $activeCashSessionsQuery->where('branch_id', $branchId);
        }
        $activeCashSessions = $activeCashSessionsQuery->count();

        $todayExpensesQuery = Expense::whereDate('expense_date', $today);
        if ($branchId) {
            $todayExpensesQuery->where('branch_id', $branchId);
        }
        $todayExpenses = $todayExpensesQuery->sum('amount');

        // Estimate today's gross profit
        $todayItems = SaleItem::whereHas('sale', function ($q) use ($today, $branchId) {
            $q->where('status', 'completed')->whereDate('sold_at', $today);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })->get();

        $cogs = $todayItems->sum(function ($item) {
            return (float) $item->unit_cost_snapshot * (float) $item->quantity * (int) $item->conversion_factor_snapshot;
        });

        $estimatedGrossProfit = max(0, $todaySales - $cogs);

        // Top 5 selling products today
        $topProducts = SaleItem::whereHas('sale', function ($q) use ($today) {
            $q->where('status', 'completed')->whereDate('sold_at', $today);
        })
        ->select('product_name_snapshot', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
        ->groupBy('product_name_snapshot')
        ->orderByDesc('total_qty')
        ->limit(5)
        ->get();

        return [
            'today_sales' => $todaySales,
            'month_sales' => $monthSales,
            'today_transactions' => $todayTransactions,
            'average_ticket' => $averageTicket,
            'occupied_tables' => $occupiedTables,
            'total_tables' => $totalTables,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'active_cash_sessions' => $activeCashSessions,
            'today_expenses' => $todayExpenses,
            'cogs' => $cogs,
            'estimated_gross_profit' => $estimatedGrossProfit,
            'top_products' => $topProducts,
        ];
    }

    /**
     * Filtered sales report.
     */
    public function getFilteredSalesReport(Carbon $startDate, Carbon $endDate, ?int $userId = null, ?int $paymentMethodId = null, ?int $branchId = null): array
    {
        $branchId = $branchId ?? app(TenantContext::class)->getBranchId() ?? session('active_branch_id');

        $salesQuery = Sale::where('status', 'completed')
            ->whereBetween('sold_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->with(['user', 'payments.paymentMethod', 'items']);

        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
        }

        if ($userId) {
            $salesQuery->where('user_id', $userId);
        }

        if ($paymentMethodId) {
            $salesQuery->whereHas('payments', function ($q) use ($paymentMethodId) {
                $q->where('payment_method_id', $paymentMethodId);
            });
        }

        $sales = $salesQuery->latest('sold_at')->get();

        $totalSales = $sales->sum('total');
        $totalSubtotal = $sales->sum('subtotal');
        $totalDiscounts = $sales->sum('discount_total');
        $totalTax = $sales->sum('tax_total');

        $cogs = $sales->sum(function ($sale) {
            return $sale->calculateTotalCost();
        });

        $grossProfit = $totalSales - $cogs;
        $profitMargin = $totalSales > 0 ? round(($grossProfit / $totalSales) * 100, 1) : 0;

        return [
            'sales' => $sales,
            'total_sales' => $totalSales,
            'total_subtotal' => $totalSubtotal,
            'total_discounts' => $totalDiscounts,
            'total_tax' => $totalTax,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'profit_margin' => $profitMargin,
            'count' => $sales->count(),
        ];
    }
}
