<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::with(['category', 'user'])
            ->latest('expense_date')
            ->paginate(25);

        $categories = ExpenseCategory::all();

        return view('expenses.index', [
            'expenses' => $expenses,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,bank,other',
            'description' => 'required|string|max:250',
            'receipt_number' => 'nullable|string|max:100',
            'expense_date' => 'required|date',
        ]);

        $cashSessionId = null;
        if ($request->input('payment_method') === 'cash') {
            $activeSession = CashSession::getActiveSession();
            $cashSessionId = $activeSession?->id;
        }

        Expense::create([
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

        return redirect()->route('expenses.index')->with('success', 'Gasto operativo registrado exitosamente.');
    }
}
