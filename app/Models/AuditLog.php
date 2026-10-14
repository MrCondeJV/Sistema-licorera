<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory, BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'reason',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'product_created' => 'Producto Creado',
            'product_updated' => 'Producto Modificado',
            'stock_adjusted' => 'Ajuste de Stock',
            'inventory_count_applied' => 'Conteo Físico Aplicado',
            'sale_created' => 'Venta POS',
            'sale_refunded' => 'Venta Anulada / Reembolso',
            'customer_debt_charged' => 'Venta a Crédito / Fiada',
            'cash_session_opened' => 'Apertura de Turno de Caja',
            'cash_session_closed' => 'Cierre de Turno de Caja',
            'cash_movement_in' => 'Ingreso de Efectivo a Caja',
            'cash_movement_out' => 'Retiro de Efectivo de Caja',
            'account_opened' => 'Apertura de Mesa / Cuenta',
            'table_item_added' => 'Ítem Agregado a Mesa',
            'table_item_removed' => 'Ítem Retirado de Mesa',
            'account_transferred' => 'Traslado de Mesa',
            'account_cancelled' => 'Cancelación de Mesa',
            'expense_created' => 'Gasto Operativo',
            'purchase_created' => 'Orden de Compra',
            'purchase_received' => 'Recepción de Mercancía',
            'supplier_created' => 'Proveedor Creado',
            'supplier_quick_created' => 'Proveedor Rápido Creado',
            'supplier_updated' => 'Proveedor Modificado',
            'supplier_status_toggled' => 'Estado de Proveedor',
            'customer_created' => 'Cliente Registrado',
            'debt_created' => 'Deuda / Fiado Registrado',
            'debt_payment_received' => 'Abono a Deuda Recibido',
            'user_created' => 'Empleado Creado',
            'user_password_reset' => 'Contraseña Restablecida',
            'user_pin_reset' => 'PIN Restablecido',
            'user_status_toggled' => 'Estado de Empleado Modificado',
            'settings_updated' => 'Configuración Comercial Modificada',
            'tenant_registered' => 'Empresa Registrada',
            'superadmin_tenant_created' => 'Empresa Creada (Superadmin)',
            'superadmin_tenant_updated' => 'Empresa Editada (Superadmin)',
            'superadmin_impersonation' => 'Superadmin Ingresó al Negocio',
            'superadmin_tenant_status' => 'Estado de Empresa Modificado',
            default => ucwords(str_replace('_', ' ', $this->action)),
        };
    }

    public function getActionCategoryAttribute(): string
    {
        return match (true) {
            str_starts_with($this->action, 'product_') || str_starts_with($this->action, 'stock_') || $this->action === 'inventory_count_applied' => 'Inventario',
            str_starts_with($this->action, 'sale_') => 'Ventas',
            str_starts_with($this->action, 'cash_') => 'Caja',
            str_starts_with($this->action, 'account_') || str_starts_with($this->action, 'table_') => 'Mesas',
            str_starts_with($this->action, 'expense_') => 'Gastos',
            str_starts_with($this->action, 'purchase_') || str_starts_with($this->action, 'supplier_') => 'Compras y Proveedores',
            str_starts_with($this->action, 'customer_') || str_starts_with($this->action, 'debt_') => 'Clientes y Fiados',
            str_starts_with($this->action, 'user_') || $this->action === 'settings_updated' || str_starts_with($this->action, 'superadmin_') => 'Seguridad y Accesos',
            default => 'General',
        };
    }

    public function getActionBadgeClassAttribute(): string
    {
        return match ($this->action_category) {
            'Inventario' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
            'Ventas' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400 border-purple-200 dark:border-purple-800',
            'Caja' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/60 dark:text-cyan-400 border-cyan-200 dark:border-cyan-800',
            'Mesas' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-400 border-sky-200 dark:border-sky-800',
            'Gastos' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border-rose-200 dark:border-rose-800',
            'Compras y Proveedores' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400 border-blue-200 dark:border-blue-800',
            'Clientes y Fiados' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border-amber-200 dark:border-amber-800',
            'Seguridad y Accesos' => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
        };
    }

    public function getFormattedChangesAttribute(): array
    {
        $fieldLabels = [
            'name' => 'Nombre',
            'sku' => 'SKU / Referencia',
            'barcode' => 'Código de Barras',
            'category_id' => 'Categoría',
            'category' => 'Categoría',
            'price_buy' => 'Costo Unitario ($)',
            'price_sell' => 'Precio Venta ($)',
            'stock' => 'Stock Actual',
            'stock_min' => 'Stock Mínimo',
            'is_active' => 'Estado Activo',
            'is_beer' => 'Es Cerveza',
            'amount' => 'Monto ($)',
            'total' => 'Total ($)',
            'sale_number' => 'Nº Venta',
            'item_count' => 'Cant. Ítems',
            'customer' => 'Cliente',
            'reason' => 'Motivo',
            'table' => 'Mesa',
            'quantity' => 'Cantidad',
            'quantity_removed' => 'Cantidad Retirada',
            'unit_price' => 'Precio Unitario ($)',
            'payment_method' => 'Método de Pago',
            'description' => 'Descripción',
            'receipt_number' => 'Nº Recibo / Comprobante',
            'notes' => 'Notas',
            'role' => 'Rol Asignado',
            'phone' => 'Teléfono',
            'tax_id' => 'Documento / NIT',
            'credit_limit' => 'Límite de Crédito ($)',
        ];

        $changes = [];

        if (!empty($this->old_values) && !empty($this->new_values)) {
            foreach ($this->new_values as $key => $newVal) {
                $oldVal = $this->old_values[$key] ?? null;
                if ($oldVal != $newVal) {
                    $changes[] = [
                        'field' => $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key)),
                        'old' => $this->formatVal($key, $oldVal),
                        'new' => $this->formatVal($key, $newVal),
                        'type' => 'modified',
                    ];
                }
            }
        } elseif (!empty($this->new_values)) {
            foreach ($this->new_values as $key => $newVal) {
                $changes[] = [
                    'field' => $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key)),
                    'old' => null,
                    'new' => $this->formatVal($key, $newVal),
                    'type' => 'created',
                ];
            }
        } elseif (!empty($this->old_values)) {
            foreach ($this->old_values as $key => $oldVal) {
                $changes[] = [
                    'field' => $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key)),
                    'old' => $this->formatVal($key, $oldVal),
                    'new' => null,
                    'type' => 'deleted',
                ];
            }
        }

        return $changes;
    }

    protected function formatVal(string $key, mixed $val): string
    {
        if (is_null($val)) return '-';
        if (is_bool($val)) return $val ? 'Sí / Activo' : 'No / Inactivo';
        if (is_array($val)) return json_encode($val, JSON_UNESCAPED_UNICODE);
        if (in_array($key, ['price_buy', 'price_sell', 'amount', 'total', 'unit_price', 'credit_limit'])) {
            return '$' . number_format((float)$val, 0, ',', '.') . ' COP';
        }
        return (string)$val;
    }
}
