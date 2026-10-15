<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Table;
use App\Services\SaleService;
use Exception;
use Illuminate\Http\Request;

class PosController extends Controller
{
    protected SaleService $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        // Check if there is an open cash shift for this user, register or store
        $openCashSession = CashSession::getActiveSession($user->id);

        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $products = Product::where('is_active', true)
            ->with(['presentations' => function ($q) {
                $q->where('is_active', true);
            }, 'category'])
            ->orderByDesc('is_favorite')
            ->orderBy('name')
            ->get();

        $tables = Table::where('is_active', true)
            ->with(['activeAccount.items'])
            ->orderBy('sort_order')
            ->get();

        $customers = Customer::where('is_active', true)
            ->orderBy('name')
            ->get();

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('pos.index', [
            'openCashSession' => $openCashSession,
            'categories' => $categories,
            'products' => $products,
            'tables' => $tables,
            'customers' => $customers,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function quickCustomer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'tax_id' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:200',
        ]);

        $tenantId = app(\App\Services\TenantContext::class)->getTenantId()
            ?? session('active_tenant_id')
            ?? \App\Models\Tenant::first()?->id;

        $customer = Customer::create([
            'tenant_id' => $tenantId,
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'tax_id' => $request->input('tax_id'),
            'address' => $request->input('address'),
            'credit_limit' => 0,
            'current_debt' => 0,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'customer' => $customer,
        ]);
    }

    public function checkout(Request $request)
    {
        $chargeToDebt = filter_var($request->input('charge_to_debt'), FILTER_VALIDATE_BOOLEAN);

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.product_presentation_id' => 'nullable|exists:product_presentations,id',
            'charge_to_debt' => 'nullable|boolean',
            'payments' => $chargeToDebt ? 'nullable|array' : 'required|array|min:1',
            'payments.*.payment_method_id' => 'required_with:payments|exists:payment_methods,id',
            'payments.*.amount' => 'required_with:payments|numeric|min:0',
            'account_id' => 'nullable|exists:accounts,id',
            'customer_id' => $chargeToDebt ? 'required|exists:customers,id' : 'nullable|exists:customers,id',
            'discount_total' => 'nullable|numeric|min:0',
        ]);

        $user = auth()->user();

        // Resolve active cash session (opened by user or store admin)
        $cashSession = CashSession::getActiveSession($user->id);

        if (!$cashSession) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un turno de caja abierto en este momento. Por favor, abre caja antes de realizar ventas.',
            ], 422);
        }

        try {
            $sale = $this->saleService->createSale(
                $request->input('items'),
                $chargeToDebt ? [] : ($request->input('payments') ?? []),
                $cashSession,
                $user,
                $request->input('account_id'),
                $request->input('customer_id'),
                (float) ($request->input('discount_total') ?? 0),
                $chargeToDebt
            );

            $msg = $chargeToDebt
                ? "¡Venta {$sale->sale_number} cargada exitosamente a la cuenta del cliente!"
                : "¡Venta {$sale->sale_number} completada con éxito!";

            return response()->json([
                'success' => true,
                'message' => $msg,
                'sale' => $sale->load(['items', 'payments.paymentMethod', 'customer']),
                'receipt_url' => route('pos.receipt', $sale->id),
                'whatsapp_url' => $sale->getWhatsappUrl(),
                'whatsapp_message' => $sale->generateWhatsappMessage(),
                'customer_phone' => $sale->customer?->phone ?? '',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function receipt(Sale $sale)
    {
        $sale->load(['items', 'payments.paymentMethod', 'user', 'account.table']);
        return view('pos.receipt', [
            'sale' => $sale,
        ]);
    }
}
