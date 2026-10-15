<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::with(['user'])
            ->latest('expense_date')
            ->latest('id')
            ->paginate(25);

        return view('expenses.index', [
            'expenses' => $expenses,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,bank,other',
            'description' => 'required|string|max:250',
            'receipt_number' => 'nullable|string|max:100',
            'expense_date' => 'required|date',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
        ]);

        $cashSessionId = null;
        if ($request->input('payment_method') === 'cash') {
            $activeSession = CashSession::getActiveSession();
            $cashSessionId = $activeSession?->id;
        }

        $expense = Expense::create([
            'tenant_id' => app(\App\Services\TenantContext::class)->getTenantId(),
            'branch_id' => app(\App\Services\TenantContext::class)->getBranchId(),
            'expense_category_id' => $request->input('expense_category_id'),
            'cash_session_id' => $cashSessionId,
            'user_id' => auth()->id(),
            'amount' => $request->input('amount'),
            'payment_method' => $request->input('payment_method'),
            'description' => $request->input('description'),
            'receipt_number' => $request->input('receipt_number'),
            'expense_date' => $request->input('expense_date'),
        ]);

        AuditService::log(
            'expense_created',
            'Expense',
            $expense->id,
            null,
            [
                'amount' => (float) $expense->amount,
                'payment_method' => $expense->payment_method,
                'description' => $expense->description,
                'receipt_number' => $expense->receipt_number,
            ],
            "Gasto operativo registrado: $" . number_format($expense->amount, 0, ',', '.') . " COP - {$expense->description}"
        );

        return redirect()->route('expenses.index')->with('success', 'Gasto operativo registrado exitosamente.');
    }
}
