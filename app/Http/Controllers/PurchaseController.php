<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Exception;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    protected PurchaseService $purchaseService;

    public function __construct(PurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    public function index()
    {
        $purchases = Purchase::with(['supplier', 'user', 'items.product'])
            ->latest()
            ->paginate(20);

        return view('purchases.index', [
            'purchases' => $purchases,
        ]);
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->with('presentations')->orderBy('name')->get();

        return view('purchases.create', [
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'receive_immediately' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.product_presentation_id' => 'nullable|exists:product_presentations,id',
            'items.*.conversion_factor' => 'nullable|integer|min:1',
        ]);

        try {
            $purchase = $this->purchaseService->createPurchase(
                (int) $request->input('supplier_id'),
                $request->input('items'),
                auth()->user(),
                $request->input('invoice_number'),
                $request->input('notes'),
                $request->boolean('receive_immediately')
            );

            return redirect()->route('purchases.index')->with('success', "Compra #{$purchase->id} registrada exitosamente.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function receive(Purchase $purchase)
    {
        try {
            $this->purchaseService->receivePurchase($purchase, auth()->user());
            return back()->with('success', "Mercancía recibida e inventario actualizado con costo promedio ponderado.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
