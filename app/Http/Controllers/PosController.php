<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Category;
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

        // Check if there is an open cash shift for this user or register
        $openCashSession = CashSession::where('status', 'open')
            ->where('user_id', $user->id)
            ->first();

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
            ->with('activeAccount')
            ->orderBy('sort_order')
            ->get();

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('pos.index', [
            'openCashSession' => $openCashSession,
            'categories' => $categories,
            'products' => $products,
            'tables' => $tables,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.product_presentation_id' => 'nullable|exists:product_presentations,id',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|exists:payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0',
            'account_id' => 'nullable|exists:accounts,id',
            'customer_id' => 'nullable|exists:customers,id',
            'discount_total' => 'nullable|numeric|min:0',
        ]);

        $user = auth()->user();

        // Resolve active cash session
        $cashSession = CashSession::where('status', 'open')
            ->where('user_id', $user->id)
            ->first();

        if (!$cashSession) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un turno de caja abierto. Por favor, abre caja antes de realizar ventas.',
            ], 422);
        }

        try {
            $sale = $this->saleService->createSale(
                $request->input('items'),
                $request->input('payments'),
                $cashSession,
                $user,
                $request->input('account_id'),
                $request->input('customer_id'),
                (float) ($request->input('discount_total') ?? 0)
            );

            return response()->json([
                'success' => true,
                'message' => "¡Venta {$sale->sale_number} completada con éxito!",
                'sale' => $sale->load(['items', 'payments.paymentMethod']),
                'receipt_url' => route('pos.receipt', $sale->id),
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
