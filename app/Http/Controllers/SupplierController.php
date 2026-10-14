<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\AuditService;
use App\Services\TenantContext;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $query = Supplier::withCount('purchases')->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%")
                  ->orWhere('tax_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->paginate(15)->withQueryString();
        $totalSuppliers = Supplier::count();
        $activeSuppliers = Supplier::where('is_active', true)->count();

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'search' => $search,
            'totalSuppliers' => $totalSuppliers,
            'activeSuppliers' => $activeSuppliers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'business_name' => 'nullable|string|max:150',
            'tax_id' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:200',
            'contact_person' => 'nullable|string|max:100',
        ], [
            'name.required' => 'El nombre del proveedor es obligatorio.',
        ]);

        $tenantId = app(TenantContext::class)->getTenantId();
        $validated['tenant_id'] = $tenantId;
        $validated['is_active'] = true;

        $supplier = Supplier::create($validated);

        AuditService::log('supplier_created', 'Supplier', $supplier->id, null, $supplier->toArray(), "Creación de proveedor: {$supplier->name}");

        return redirect()->route('suppliers.index')->with('success', "Proveedor '{$supplier->name}' registrado exitosamente.");
    }

    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'tax_id' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:100',
        ]);

        $tenantId = app(TenantContext::class)->getTenantId();
        $validated['tenant_id'] = $tenantId;
        $validated['is_active'] = true;

        $supplier = Supplier::create($validated);

        AuditService::log('supplier_quick_created', 'Supplier', $supplier->id, null, $supplier->toArray(), "Creación rápida de proveedor: {$supplier->name}");

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'supplier' => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'tax_id' => $supplier->tax_id,
                ],
            ]);
        }

        return back()->with('success', "Proveedor '{$supplier->name}' creado.");
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'business_name' => 'nullable|string|max:150',
            'tax_id' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:200',
            'contact_person' => 'nullable|string|max:100',
        ], [
            'name.required' => 'El nombre del proveedor es obligatorio.',
        ]);

        $old = $supplier->toArray();
        $supplier->update($validated);

        AuditService::log('supplier_updated', 'Supplier', $supplier->id, $old, $supplier->toArray(), "Actualización de proveedor: {$supplier->name}");

        return redirect()->route('suppliers.index')->with('success', "Proveedor '{$supplier->name}' actualizado correctamente.");
    }

    public function toggleStatus(Supplier $supplier)
    {
        $supplier->is_active = !$supplier->is_active;
        $supplier->save();

        $statusText = $supplier->is_active ? 'activado' : 'desactivado';
        AuditService::log('supplier_status_toggled', 'Supplier', $supplier->id, null, ['is_active' => $supplier->is_active], "Proveedor {$statusText}");

        return back()->with('success', "Proveedor '{$supplier->name}' {$statusText}.");
    }
}
