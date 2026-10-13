<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\AuditService;
use App\Services\TenantContext;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $tenant = app(TenantContext::class)->getTenant();
        $settings = $tenant->settings()->pluck('value', 'key')->toArray();

        return view('settings.index', [
            'tenant' => $tenant,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $tenant = app(TenantContext::class)->getTenant();

        $validated = $request->validate([
            'business_name' => 'required|string|max:150',
            'tax_id' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:200',
            'phone' => 'nullable|string|max:50',
            'receipt_message' => 'nullable|string|max:300',
            'printer_width' => 'required|in:58mm,80mm',
            'lock_timeout_minutes' => 'required|integer|min:1|max:120',
            'debts_module_enabled' => 'nullable|boolean',
        ]);

        $tenant->name = $validated['business_name'];
        $tenant->tax_id = $validated['tax_id'];
        $tenant->address = $validated['address'];
        $tenant->phone = $validated['phone'];
        $tenant->save();

        $tenant->setSetting('business_name', $validated['business_name']);
        $tenant->setSetting('tax_id', $validated['tax_id'] ?? '');
        $tenant->setSetting('address', $validated['address'] ?? '');
        $tenant->setSetting('phone', $validated['phone'] ?? '');
        $tenant->setSetting('receipt_message', $validated['receipt_message'] ?? '');
        $tenant->setSetting('printer_width', $validated['printer_width']);
        $tenant->setSetting('lock_timeout_minutes', (string)$validated['lock_timeout_minutes']);
        $tenant->setSetting('debts_module_enabled', $request->has('debts_module_enabled') ? '1' : '0');

        AuditService::log('settings_updated', 'Tenant', $tenant->id, null, null, "Actualización de configuración comercial");

        return back()->with('success', 'Configuración actualizada exitosamente.');
    }
}
