<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Exception;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $query = Product::with(['category', 'presentations']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->input('filter') === 'low_stock') {
            $query->whereColumn('stock', '<=', 'stock_min')->where('stock', '>', 0);
        } elseif ($request->input('filter') === 'out_of_stock') {
            $query->where('stock', '<=', 0);
        }

        $products = $query->orderBy('name')->paginate(25);
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();

        return view('inventory.index', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();
        return view('inventory.create', ['categories' => $categories]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'sku' => 'required|string|max:50',
            'barcode' => 'nullable|string|max:50',
            'name' => 'required|string|max:150',
            'brand' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'price_buy' => 'required|numeric|min:0',
            'price_sell' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'stock_min' => 'required|numeric|min:0',
            'base_unit' => 'required|string|in:unidad,botella,lata',
            'is_beer' => 'nullable|boolean',
            'is_liquor' => 'nullable|boolean',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'is_favorite' => 'nullable|boolean',
            'presentations' => 'nullable|array',
            'presentations.*.name' => 'required_with:presentations|string',
            'presentations.*.conversion_factor' => 'required_with:presentations|integer|min:1',
            'presentations.*.sale_price' => 'required_with:presentations|numeric|min:0',
        ]);

        $isLiquor = $request->boolean('is_liquor');
        $isBeer = $request->boolean('is_beer');

        // Liquor Rule: strictly bottle only
        if ($isLiquor && !empty($request->input('presentations'))) {
            return back()->withInput()->with('error', 'Los licores no admiten presentaciones de fraccionamiento. Solo botella completa.');
        }

        $product = Product::create([
            'category_id' => $validated['category_id'],
            'sku' => $validated['sku'],
            'barcode' => $validated['barcode'] ?? null,
            'name' => $validated['name'],
            'brand' => $validated['brand'] ?? null,
            'description' => $validated['description'] ?? null,
            'price_buy' => $validated['price_buy'],
            'price_sell' => $validated['price_sell'],
            'stock' => $validated['stock'],
            'stock_min' => $validated['stock_min'],
            'base_unit' => $validated['base_unit'],
            'is_beer' => $isBeer,
            'is_liquor' => $isLiquor,
            'tax_percentage' => $validated['tax_percentage'] ?? 0,
            'is_favorite' => $request->boolean('is_favorite'),
            'is_active' => true,
        ]);

        // Initial stock movement
        if ($product->stock > 0) {
            StockMovement::create([
                'tenant_id' => $product->tenant_id,
                'branch_id' => app(\App\Services\TenantContext::class)->getBranchId(),
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'movement_type' => 'in',
                'quantity' => $product->stock,
                'stock_before' => 0,
                'stock_after' => $product->stock,
                'reason' => 'Inventario inicial',
            ]);
        }

        // Presentations for beers
        if ($isBeer && !empty($request->input('presentations'))) {
            foreach ($request->input('presentations') as $p) {
                if (!empty($p['name']) && !empty($p['conversion_factor']) && !empty($p['sale_price'])) {
                    ProductPresentation::create([
                        'tenant_id' => $product->tenant_id,
                        'product_id' => $product->id,
                        'name' => $p['name'],
                        'conversion_factor' => (int)$p['conversion_factor'],
                        'sale_price' => (float)$p['sale_price'],
                        'is_active' => true,
                    ]);
                }
            }
        }

        return redirect()->route('inventory.index')->with('success', "Producto '{$product->name}' creado correctamente.");
    }

    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();
        $product->load('presentations');
        return view('inventory.edit', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'sku' => 'required|string|max:50',
            'barcode' => 'nullable|string|max:50',
            'name' => 'required|string|max:150',
            'brand' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'price_buy' => 'required|numeric|min:0',
            'price_sell' => 'required|numeric|min:0',
            'stock_min' => 'required|numeric|min:0',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'is_favorite' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $product->update([
            'category_id' => $validated['category_id'],
            'sku' => $validated['sku'],
            'barcode' => $validated['barcode'] ?? null,
            'name' => $validated['name'],
            'brand' => $validated['brand'] ?? null,
            'description' => $validated['description'] ?? null,
            'price_buy' => $validated['price_buy'],
            'price_sell' => $validated['price_sell'],
            'stock_min' => $validated['stock_min'],
            'tax_percentage' => $validated['tax_percentage'] ?? 0,
            'is_favorite' => $request->boolean('is_favorite'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('inventory.index')->with('success', "Producto actualizado correctamente.");
    }

    public function quickAdjust(Request $request, Product $product)
    {
        $request->validate([
            'new_stock' => 'required|numeric|min:0',
            'reason' => 'required|string|max:200',
        ]);

        try {
            $this->inventoryService->adjustStock(
                $product,
                (float) $request->input('new_stock'),
                $request->input('reason'),
                auth()->user()
            );

            return back()->with('success', "Stock de '{$product->name}' ajustado exitosamente.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function kardex(Request $request)
    {
        $query = StockMovement::with(['product', 'user'])->latest('id');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->input('movement_type'));
        }

        $movements = $query->paginate(30);
        $products = Product::orderBy('name')->get();

        return view('inventory.kardex', [
            'movements' => $movements,
            'products' => $products,
        ]);
    }

    public function counts()
    {
        $counts = InventoryCount::with(['user', 'items'])->latest()->paginate(15);
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('inventory.counts', [
            'counts' => $counts,
            'products' => $products,
        ]);
    }

    public function storeCount(Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string',
            'counts' => 'required|array',
            'counts.*.product_id' => 'required|exists:products,id',
            'counts.*.physical_stock' => 'required|numeric|min:0',
        ]);

        $count = InventoryCount::create([
            'tenant_id' => app(\App\Services\TenantContext::class)->getTenantId(),
            'branch_id' => app(\App\Services\TenantContext::class)->getBranchId(),
            'user_id' => auth()->id(),
            'notes' => $request->input('notes'),
            'status' => 'pending',
        ]);

        foreach ($request->input('counts') as $c) {
            $prod = Product::find($c['product_id']);
            if ($prod) {
                $sysStock = (float) $prod->stock;
                $physStock = (float) $c['physical_stock'];
                InventoryCountItem::create([
                    'tenant_id' => $count->tenant_id,
                    'inventory_count_id' => $count->id,
                    'product_id' => $prod->id,
                    'system_stock' => $sysStock,
                    'physical_stock' => $physStock,
                    'difference' => $physStock - $sysStock,
                ]);
            }
        }

        return redirect()->route('inventory.counts')->with('success', "Conteo físico registrado. Puedes revisarlo y aplicarlo.");
    }

    public function applyCount(InventoryCount $count)
    {
        try {
            $this->inventoryService->applyPhysicalCount($count, auth()->user());
            return back()->with('success', "Conteo físico #{$count->id} aplicado al inventario.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
