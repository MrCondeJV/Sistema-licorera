<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    public function index()
    {
        $promotions = Promotion::with('products.product')->latest()->get();
        $categories = Category::where('is_active', true)->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('promotions.index', [
            'promotions' => $promotions,
            'categories' => $categories,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'type' => 'required|in:discount_percentage,fixed_price,happy_hour',
            'discount_percentage' => 'nullable|numeric|min:1|max:100',
            'fixed_price' => 'nullable|numeric|min:0',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'min_quantity' => 'required|integer|min:1',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $promotion = Promotion::create([
                'tenant_id' => app(\App\Services\TenantContext::class)->getTenantId(),
                'name' => $validated['name'],
                'type' => $validated['type'],
                'discount_percentage' => $validated['discount_percentage'] ?? null,
                'fixed_price' => $validated['fixed_price'] ?? null,
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'min_quantity' => $validated['min_quantity'],
                'is_active' => true,
            ]);

            if (!empty($request->input('product_ids'))) {
                foreach ($request->input('product_ids') as $pid) {
                    PromotionProduct::create([
                        'tenant_id' => $promotion->tenant_id,
                        'promotion_id' => $promotion->id,
                        'product_id' => $pid,
                    ]);
                }
            }
        });

        return redirect()->route('promotions.index')->with('success', 'Promoción / Happy Hour creada con éxito.');
    }

    public function toggle(Promotion $promotion)
    {
        $promotion->is_active = !$promotion->is_active;
        $promotion->save();

        return back()->with('success', 'Estado de la promoción actualizado.');
    }
}
