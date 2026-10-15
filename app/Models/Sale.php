<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'account_id',
        'user_id',
        'cash_session_id',
        'customer_id',
        'sale_number',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'sold_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function calculateTotalCost(): float
    {
        return (float) $this->items->sum(function ($item) {
            return (float) $item->unit_cost_snapshot * (float) $item->quantity * (int) $item->conversion_factor_snapshot;
        });
    }

    public function calculateGrossProfit(): float
    {
        return (float) $this->total - $this->calculateTotalCost();
    }

    public function generateWhatsappMessage(): string
    {
        $businessName = $this->tenant?->name ?? config('app.name', 'Licorera POS');
        $date = $this->sold_at ? $this->sold_at->format('d/m/Y h:i A') : now()->format('d/m/Y h:i A');

        $lines = [];
        $lines[] = "*" . mb_strtoupper($businessName, 'UTF-8') . "*";
        $lines[] = "*Comprobante:* #{$this->sale_number}";
        $lines[] = "*Fecha:* {$date}";
        if ($this->user) {
            $lines[] = "*Atendido por:* {$this->user->name}";
        }
        if ($this->customer) {
            $lines[] = "*Cliente:* {$this->customer->name}";
        }
        if ($this->account && $this->account->table) {
            $lines[] = "*Mesa:* {$this->account->table->name}";
        }
        $lines[] = "--------------------------------";
        $lines[] = "*DETALLE DE COMPRA:*";

        foreach ($this->items as $item) {
            $pres = ($item->presentation_name_snapshot && $item->presentation_name_snapshot !== 'Unidad')
                ? " ({$item->presentation_name_snapshot})"
                : "";
            $qty = rtrim(rtrim((string)$item->quantity, '0'), '.');
            $lines[] = "• {$qty}x {$item->product_name_snapshot}{$pres} - $" . number_format($item->subtotal, 0, ',', '.');
        }

        $lines[] = "--------------------------------";
        if ($this->discount_total > 0) {
            $lines[] = "*Descuento:* -$" . number_format($this->discount_total, 0, ',', '.');
        }
        $lines[] = "*TOTAL: $" . number_format($this->total, 0, ',', '.') . " COP*";

        if ($this->payments->isNotEmpty()) {
            $methods = $this->payments->map(function ($p) {
                return ($p->paymentMethod?->name ?? 'Pago') . " ($" . number_format($p->amount, 0, ',', '.') . ")";
            })->implode(', ');
            $lines[] = "*Medio de Pago:* {$methods}";
        }

        $lines[] = "";
        $receiptUrl = route('pos.receipt', $this->id);
        $lines[] = "*Comprobante digital:*";
        $lines[] = $receiptUrl;
        $lines[] = "";
        $lines[] = "¡Muchas gracias por su compra!";

        return implode("\n", $lines);
    }

    public function getWhatsappUrl(?string $phone = null): string
    {
        $cleanPhone = '';
        if ($phone) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($cleanPhone) === 10 && str_starts_with($cleanPhone, '3')) {
                $cleanPhone = '57' . $cleanPhone;
            }
        } elseif ($this->customer && $this->customer->phone) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $this->customer->phone);
            if (strlen($cleanPhone) === 10 && str_starts_with($cleanPhone, '3')) {
                $cleanPhone = '57' . $cleanPhone;
            }
        }

        $message = $this->generateWhatsappMessage();
        $encodedText = rawurlencode($message);

        if (!empty($cleanPhone)) {
            return "https://api.whatsapp.com/send?phone={$cleanPhone}&text={$encodedText}";
        }

        return "https://api.whatsapp.com/send?text={$encodedText}";
    }
}
