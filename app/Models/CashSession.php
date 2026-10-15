<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'cash_register_id',
        'user_id',
        'opening_amount',
        'expected_cash',
        'counted_cash',
        'difference',
        'status',
        'closing_notes',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opening_amount' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'counted_cash' => 'decimal:2',
        'difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function customerPayments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Resolve the active open cash session.
     * Priority:
     * 1. If a specific register is set in session('active_cash_register_id')
     * 2. If the user opened a session in current branch that is still open
     * 3. Any active open session within the current branch / tenant scope
     */
    public static function getActiveSession(?int $userId = null, ?int $registerId = null, ?int $branchId = null): ?self
    {
        $userId = $userId ?? auth()->id();
        $registerId = $registerId ?? session('active_cash_register_id');
        $branchId = $branchId ?? app(\App\Services\TenantContext::class)->getBranchId() ?? session('active_branch_id');

        if ($registerId) {
            $regSession = static::where('status', 'open')
                ->where('cash_register_id', $registerId)
                ->when($branchId, fn($q) => $q->where(fn($sub) => $sub->where('branch_id', $branchId)->orWhereNull('branch_id')))
                ->with(['cashRegister', 'user', 'movements.user', 'sales'])
                ->latest()
                ->first();

            if ($regSession) {
                return $regSession;
            }
        }

        if ($userId) {
            $userSession = static::where('status', 'open')
                ->where('user_id', $userId)
                ->when($branchId, fn($q) => $q->where(fn($sub) => $sub->where('branch_id', $branchId)->orWhereNull('branch_id')))
                ->with(['cashRegister', 'user', 'movements.user', 'sales'])
                ->latest()
                ->first();

            if ($userSession) {
                return $userSession;
            }
        }

        $session = static::where('status', 'open')
            ->when($branchId, fn($q) => $q->where(fn($sub) => $sub->where('branch_id', $branchId)->orWhereNull('branch_id')))
            ->with(['cashRegister', 'user', 'movements.user', 'sales'])
            ->latest()
            ->first();

        if ($session) {
            return $session;
        }

        // Global fallback within tenant if no branch-specific session found
        return static::where('status', 'open')
            ->with(['cashRegister', 'user', 'movements.user', 'sales'])
            ->latest()
            ->first();
    }

    public function calculateCashSummary(): array
    {
        $base = (float) $this->opening_amount;

        // Cash sales: payments for sales in this session where payment_method is_cash = true
        $cashSales = (float) Payment::whereHas('sale', function ($query) {
            $query->where('cash_session_id', $this->id);
        })->whereHas('paymentMethod', function ($query) {
            $query->where('is_cash', true);
        })->where('status', 'confirmed')->sum('amount');

        // Customer debt payments received in cash during this session
        $cashCustomerPayments = (float) $this->customerPayments()
            ->whereHas('paymentMethod', function ($query) {
                $query->where('is_cash', true);
            })->sum('amount');

        // Cash movements in / out
        $cashIn = (float) $this->movements()->where('movement_type', 'in')->sum('amount');
        $cashOut = (float) $this->movements()->where('movement_type', 'out')->sum('amount');

        // Expenses paid in cash
        $cashExpenses = (float) $this->expenses()->where('payment_method', 'cash')->sum('amount');

        $expected = $base + $cashSales + $cashCustomerPayments + $cashIn - $cashOut - $cashExpenses;

        // Non-cash electronic payments summary (Sales + Customer Debt Payments)
        $saleElectronic = Payment::whereHas('sale', function ($query) {
            $query->where('cash_session_id', $this->id);
        })->whereHas('paymentMethod', function ($query) {
            $query->where('is_cash', false);
        })->where('status', 'confirmed')->with('paymentMethod')->get();

        $customerElectronic = $this->customerPayments()
            ->whereHas('paymentMethod', function ($query) {
                $query->where('is_cash', false);
            })->with('paymentMethod')->get();

        $electronicPayments = $saleElectronic->concat($customerElectronic)
            ->groupBy('payment_method_id')
            ->map(function ($items) {
                return [
                    'method' => $items->first()->paymentMethod->name,
                    'total' => $items->sum('amount'),
                ];
            })->values()->toArray();

        return [
            'opening_amount' => $base,
            'cash_sales' => $cashSales,
            'cash_customer_payments' => $cashCustomerPayments,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'cash_expenses' => $cashExpenses,
            'expected_cash' => $expected,
            'electronic_payments' => $electronicPayments,
        ];
    }
}
