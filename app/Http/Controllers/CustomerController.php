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

        Customer::create([
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

        return redirect()->route('customers.index')->with('success', 'Cliente registrado correctamente.');
    }

    public function debts(Customer $customer)
    {
        $customer->load(['debts.sale', 'payments.paymentMethod']);
        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        return view('customers.debts', [
            'customer' => $customer,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function storeDebt(Request $request, Customer $customer)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:250',
            'due_date' => 'nullable|date',
        ]);

        $amount = (float) $request->input('amount');

        // Check credit limit
        if ($customer->credit_limit > 0 && ($customer->current_debt + $amount) > $customer->credit_limit) {
            return back()->with('error', "El monto supera el cupo de crédito del cliente ($" . number_format($customer->credit_limit, 0, ',', '.') . ").");
        }

        DB::transaction(function () use ($customer, $amount, $request) {
            CustomerDebt::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'user_id' => auth()->id(),
                'original_amount' => $amount,
                'remaining_amount' => $amount,
                'status' => 'pending',
                'notes' => $request->input('notes'),
                'due_date' => $request->input('due_date'),
            ]);

            $customer->current_debt += $amount;
            $customer->save();

            AuditService::log('debt_created', 'CustomerDebt', $customer->id, null, ['amount' => $amount], "Registro de fiado / deuda");
        });

        return back()->with('success', 'Deuda/fiado registrado correctamente.');
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
