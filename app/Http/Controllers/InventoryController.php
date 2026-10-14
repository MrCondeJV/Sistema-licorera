<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\StockMovement;
use App\Services\AuditService;
use App\Services\InventoryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $isLiquor = $request->boolean('is_liquor');
        $isBeer = $request->boolean('is_beer');

        // Liquor Rule: strictly bottle only
        if ($isLiquor && !empty($request->input('presentations'))) {
            return back()->withInput()->with('error', 'Los licores no admiten presentaciones de fraccionamiento. Solo se venden por botella completa.');
        }

        $rules = [
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
            'image' => 'nullable|image|max:5120',
            'is_favorite' => 'nullable|boolean',
            'presentations' => 'nullable|array',
        ];

        if ($isBeer && $request->has('presentations')) {
            $rules['presentations.*.sale_price'] = 'nullable|numeric|min:0';
            $rules['presentations.*.conversion_factor'] = 'nullable|integer|min:1';
            $rules['presentations.*.name'] = 'nullable|string';
        }

        $messages = [
            'name.required' => 'El Nombre del Producto es obligatorio.',
            'category_id.required' => 'Debes seleccionar una Categoría para el producto.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'sku.required' => 'El código SKU / Referencia es obligatorio.',
            'base_unit.required' => 'Debes seleccionar la Unidad Base (Unidad, Botella o Lata).',
            'base_unit.in' => 'La unidad base debe ser: unidad, botella o lata.',
            'price_buy.required' => 'El Costo Unitario es obligatorio.',
            'price_buy.numeric' => 'El costo unitario debe ser un número.',
            'price_buy.min' => 'El costo unitario no puede ser negativo.',
            'price_sell.required' => 'El Precio de Venta al público es obligatorio.',
            'price_sell.numeric' => 'El precio de venta debe ser un número.',
            'price_sell.min' => 'El precio de venta no puede ser negativo.',
            'stock.required' => 'El Stock Inicial es obligatorio.',
            'stock.numeric' => 'El stock inicial debe ser un número.',
            'stock.min' => 'El stock inicial no puede ser negativo.',
            'stock_min.required' => 'El Stock Mínimo de alerta es obligatorio.',
            'stock_min.numeric' => 'El stock mínimo debe ser un número.',
            'stock_min.min' => 'El stock mínimo no puede ser negativo.',
            'image.image' => 'El archivo adjunto debe ser una imagen válida (JPG, PNG o WEBP).',
            'image.max' => 'La imagen seleccionada supera el límite máximo de 5MB.',
            'presentations.*.sale_price.numeric' => 'El precio de la presentación debe ser un número válido.',
            'presentations.*.sale_price.min' => 'El precio de la presentación no puede ser negativo.',
        ];

        $validated = $request->validate($rules, $messages);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
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
            'image' => $imagePath,
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

        AuditService::log(
            'product_created',
            'Product',
            $product->id,
            null,
            [
                'name' => $product->name,
                'sku' => $product->sku,
                'category_id' => $product->category_id,
                'price_buy' => (float) $product->price_buy,
                'price_sell' => (float) $product->price_sell,
                'stock' => (float) $product->stock,
                'stock_min' => (int) $product->stock_min,
            ],
            "Creación de producto '{$product->name}' por " . (auth()->user()?->name ?? 'Usuario')
        );

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
            'image' => 'nullable|image|max:5120',
            'is_favorite' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'El Nombre del Producto es obligatorio.',
            'category_id.required' => 'Debes seleccionar una Categoría para el producto.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'sku.required' => 'El código SKU / Referencia es obligatorio.',
            'price_buy.required' => 'El Costo Unitario es obligatorio.',
            'price_buy.numeric' => 'El costo unitario debe ser un número.',
            'price_buy.min' => 'El costo unitario no puede ser negativo.',
            'price_sell.required' => 'El Precio de Venta es obligatorio.',
            'price_sell.numeric' => 'El precio de venta debe ser un número.',
            'price_sell.min' => 'El precio de venta no puede ser negativo.',
            'stock_min.required' => 'El Stock Mínimo de alerta es obligatorio.',
            'stock_min.numeric' => 'El stock mínimo debe ser un número.',
            'stock_min.min' => 'El stock mínimo no puede ser negativo.',
            'image.image' => 'El archivo adjunto debe ser una imagen válida (JPG, PNG o WEBP).',
            'image.max' => 'La imagen seleccionada supera el límite máximo de 5MB.',
        ]);

        $oldValues = [
            'name' => $product->name,
            'sku' => $product->sku,
            'category_id' => $product->category_id,
            'price_buy' => (float) $product->price_buy,
            'price_sell' => (float) $product->price_sell,
            'stock_min' => (int) $product->stock_min,
            'is_active' => (bool) $product->is_active,
        ];

        $data = [
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
        ];

        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        $newValues = [
            'name' => $product->name,
            'sku' => $product->sku,
            'category_id' => $product->category_id,
            'price_buy' => (float) $product->price_buy,
            'price_sell' => (float) $product->price_sell,
            'stock_min' => (int) $product->stock_min,
            'is_active' => (bool) $product->is_active,
        ];

        $changes = [];
        foreach ($newValues as $k => $v) {
            if ($oldValues[$k] != $v) {
                $changes[$k] = ['old' => $oldValues[$k], 'new' => $v];
            }
        }

        $reason = "Actualización de producto '{$product->name}'";
        if (!empty($changes)) {
            $reason .= " (Modificó: " . implode(', ', array_keys($changes)) . ")";
        }

        AuditService::log(
            'product_updated',
            'Product',
            $product->id,
            $oldValues,
            $newValues,
            $reason
        );

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
