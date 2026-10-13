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

    public function isOpen(): bool
    {
        return $this->status === 'open';
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

        // Cash movements in / out
        $cashIn = (float) $this->movements()->where('movement_type', 'in')->sum('amount');
        $cashOut = (float) $this->movements()->where('movement_type', 'out')->sum('amount');

        // Expenses paid in cash
        $cashExpenses = (float) $this->expenses()->where('payment_method', 'cash')->sum('amount');

        $expected = $base + $cashSales + $cashIn - $cashOut - $cashExpenses;

        // Non-cash electronic payments summary
        $electronicPayments = Payment::whereHas('sale', function ($query) {
            $query->where('cash_session_id', $this->id);
        })->whereHas('paymentMethod', function ($query) {
            $query->where('is_cash', false);
        })->where('status', 'confirmed')
          ->with('paymentMethod')
          ->get()
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
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'cash_expenses' => $cashExpenses,
            'expected_cash' => $expected,
            'electronic_payments' => $electronicPayments,
        ];
    }
}
