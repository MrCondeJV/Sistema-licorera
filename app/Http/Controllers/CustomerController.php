<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\CustomerPayment;
use App\Models\PaymentMethod;
use App\Services\AuditService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Services\InventoryService;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::withCount('debts')
            ->orderBy('name')
            ->paginate(25);

        return view('customers.index', [
            'customers' => $customers,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'tax_id' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:200',
            'credit_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::create([
            'tenant_id' => app(\App\Services\TenantContext::class)->getTenantId()
                ?? session('active_tenant_id')
                ?? \App\Models\Tenant::first()?->id,
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'tax_id' => $request->input('tax_id'),
            'address' => $request->input('address'),
            'credit_limit' => $request->input('credit_limit') ?? 0,
            'current_debt' => 0,
            'notes' => $request->input('notes'),
            'is_active' => true,
        ]);

        AuditService::log(
            'customer_created',
            'Customer',
            $customer->id,
            null,
            [
                'name' => $customer->name,
                'phone' => $customer->phone,
                'tax_id' => $customer->tax_id,
                'credit_limit' => (float) $customer->credit_limit,
            ],
            "Registro de cliente: {$customer->name}"
        );

        return redirect()->route('customers.index')->with('success', 'Cliente registrado correctamente.');
    }

    public function debts(Customer $customer)
    {
        $customer->load([
            'debts' => function ($q) {
                $q->latest();
            },
            'debts.sale.items.presentation',
            'debts.sale.items.product',
            'debts.user',
            'payments' => function ($q) {
                $q->latest();
            },
            'payments.paymentMethod',
            'payments.user',
        ]);

        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        $products = Product::where('is_active', true)
            ->with(['presentations' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();

        return view('customers.debts', [
            'customer' => $customer,
            'paymentMethods' => $paymentMethods,
            'products' => $products,
        ]);
    }

    public function storeDebt(Request $request, Customer $customer)
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
            'due_date' => 'nullable|date',
            'deduct_stock' => 'nullable',
            'items' => 'nullable|array',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_presentation_id' => 'nullable|exists:product_presentations,id',
            'items.*.name' => 'required_with:items|string|max:150',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.subtotal' => 'nullable|numeric|min:0',
        ]);

        $items = $request->input('items', []);
        $itemsSnapshot = [];
        $calculatedTotal = 0;
        $summaryParts = [];

        if (!empty($items)) {
            foreach ($items as $item) {
                $qty = (float) ($item['quantity'] ?? 1);
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $subtotal = isset($item['subtotal']) && (float) $item['subtotal'] > 0
                    ? (float) $item['subtotal']
                    : ($qty * $unitPrice);

                $calculatedTotal += $subtotal;
                $name = trim($item['name']);
                $presName = !empty($item['presentation_name']) ? trim($item['presentation_name']) : 'Unidad';
                $presSuffix = $presName !== 'Unidad' ? " ({$presName})" : '';

                $summaryParts[] = "{$qty}x {$name}{$presSuffix}";

                $itemsSnapshot[] = [
                    'product_id' => !empty($item['product_id']) ? (int) $item['product_id'] : null,
                    'product_presentation_id' => !empty($item['product_presentation_id']) ? (int) $item['product_presentation_id'] : null,
                    'product_name' => $name,
                    'presentation_name' => $presName,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];
            }
        }

        $amount = !empty($items) ? $calculatedTotal : (float) $request->input('amount');

        if ($amount <= 0) {
            return back()->with('error', 'El monto de la deuda debe ser mayor a cero.');
        }

        // Check credit limit
        if ($customer->credit_limit > 0 && ($customer->current_debt + $amount) > $customer->credit_limit) {
            return back()->with('error', "El monto supera el cupo de crédito del cliente ($" . number_format($customer->credit_limit, 0, ',', '.') . ").");
        }

        $notes = $request->input('notes');
        if (!empty($summaryParts)) {
            $itemsText = implode(', ', $summaryParts);
            $notes = $notes ? "{$itemsText} — {$notes}" : $itemsText;
        }

        $deductStock = filter_var($request->input('deduct_stock', false), FILTER_VALIDATE_BOOLEAN);

        try {
            DB::transaction(function () use ($customer, $amount, $notes, $itemsSnapshot, $request, $deductStock, $items) {
                // If requested and items contain products, deduct physical inventory
                if ($deductStock && !empty($items)) {
                    $inventoryService = app(InventoryService::class);
                    $user = auth()->user();
                    $branchId = session('active_branch_id')
                        ?? $user?->branches()->where('tenant_id', $customer->tenant_id)->first()?->id
                        ?? $customer->tenant?->branches()->where('is_main', true)->value('id')
                        ?? $customer->tenant?->branches()->value('id');

                    foreach ($items as $item) {
                        if (!empty($item['product_id'])) {
                            $product = Product::find($item['product_id']);
                            if ($product) {
                                $factor = 1;
                                if (!empty($item['product_presentation_id'])) {
                                    $pres = ProductPresentation::find($item['product_presentation_id']);
                                    if ($pres) {
                                        $factor = (int) $pres->conversion_factor;
                                    }
                                }

                                $inventoryService->deductStock(
                                    $product,
                                    (float) $item['quantity'],
                                    $factor,
                                    'sale',
                                    "Fiado cargado a cuenta del cliente {$customer->name}",
                                    $user,
                                    null,
                                    $branchId
                                );
                            }
                        }
                    }
                }

                CustomerDebt::create([
                    'tenant_id' => $customer->tenant_id,
                    'customer_id' => $customer->id,
                    'user_id' => auth()->id(),
                    'original_amount' => $amount,
                    'remaining_amount' => $amount,
                    'status' => 'pending',
                    'notes' => $notes ?: 'Fiado manual',
                    'items_snapshot' => !empty($itemsSnapshot) ? $itemsSnapshot : null,
                    'due_date' => $request->input('due_date'),
                ]);

                $customer->current_debt += $amount;
                $customer->save();

                AuditService::log('debt_created', 'CustomerDebt', $customer->id, null, ['amount' => $amount, 'items' => $itemsSnapshot], "Registro de fiado / deuda de cliente");
            });
        } catch (Exception $e) {
            return back()->with('error', 'Error al registrar el fiado: ' . $e->getMessage());
        }

        return back()->with('success', 'Deuda/fiado registrado correctamente con el detalle de lo vendido.');
    }

    public function storePayment(Request $request, Customer $customer)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:250',
        ]);

        $amount = (float) $request->input('amount');

        if ($amount > $customer->current_debt) {
            return back()->with('error', 'El abono no puede superar la deuda total pendiente.');
        }

        $paymentMethod = PaymentMethod::findOrFail($request->input('payment_method_id'));
        $activeSession = CashSession::getActiveSession();

        if ($paymentMethod->is_cash && !$activeSession) {
            return back()->with('error', 'No hay un turno de caja abierto en este momento para recibir abonos en efectivo. Por favor abre caja primero.');
        }

        DB::transaction(function () use ($customer, $amount, $request, $activeSession) {
            // Apply payment to pending debts in FIFO order
            $remainingPayment = $amount;
            $pendingDebts = $customer->debts()->where('status', 'pending')->orderBy('created_at')->get();

            foreach ($pendingDebts as $debt) {
                if ($remainingPayment <= 0) break;

                if ($remainingPayment >= $debt->remaining_amount) {
                    $remainingPayment -= $debt->remaining_amount;
                    $debt->remaining_amount = 0;
                    $debt->status = 'paid';
                    $debt->save();
                } else {
                    $debt->remaining_amount -= $remainingPayment;
                    $remainingPayment = 0;
                    $debt->save();
                }
            }

            CustomerPayment::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'user_id' => auth()->id(),
                'cash_session_id' => $activeSession?->id,
                'payment_method_id' => $request->input('payment_method_id'),
                'amount' => $amount,
                'reference' => $request->input('reference'),
                'notes' => $request->input('notes'),
            ]);

            $customer->current_debt = max(0, $customer->current_debt - $amount);
            $customer->save();

            AuditService::log('debt_payment_received', 'CustomerPayment', $customer->id, null, ['amount' => $amount], "Abono a deuda de cliente");
        });

        return back()->with('success', 'Abono registrado exitosamente.');
    }
}
