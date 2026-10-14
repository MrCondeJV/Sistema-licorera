<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Table;
use App\Services\SaleService;
use App\Services\TableAccountService;
use Exception;
use Illuminate\Http\Request;

class TableController extends Controller
{
    protected TableAccountService $accountService;
    protected SaleService $saleService;

    public function __construct(TableAccountService $accountService, SaleService $saleService)
    {
        $this->accountService = $accountService;
        $this->saleService = $saleService;
    }

    public function index()
    {
        $tables = Table::where('is_active', true)
            ->with(['activeAccount.items', 'activeAccount.opener'])
            ->orderBy('sort_order')
            ->get();

        $freeTables = Table::where('is_active', true)->where('status', 'free')->get();

        return view('tables.index', [
            'tables' => $tables,
            'freeTables' => $freeTables,
        ]);
    }

    public function show(Table $table)
    {
        $table->load(['activeAccount.items.product', 'activeAccount.items.presentation', 'activeAccount.opener']);
        $account = $table->activeAccount;

        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();
        $products = Product::where('is_active', true)
            ->with(['presentations' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();

        $freeTables = Table::where('is_active', true)
            ->where('status', 'free')
            ->where('id', '!=', $table->id)
            ->get();

        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();

        return view('tables.show', [
            'table' => $table,
            'account' => $account,
            'categories' => $categories,
            'products' => $products,
            'freeTables' => $freeTables,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function open(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:tables,id',
            'client_name' => 'nullable|string|max:100',
        ]);

        try {
            $account = $this->accountService->openAccount(
                (int) $request->input('table_id'),
                $request->input('client_name')
            );

            return redirect()->route('tables.show', $request->input('table_id'))
                ->with('success', "Cuenta abierta exitosamente.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function addItem(Request $request, Table $table)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'product_presentation_id' => 'nullable|exists:product_presentations,id',
        ]);

        $account = $table->activeAccount;
        if (!$account) {
            return back()->with('error', 'La mesa no tiene una cuenta abierta.');
        }

        try {
            $this->accountService->addItem(
                $account,
                (int) $request->input('product_id'),
                $request->input('product_presentation_id') ? (int) $request->input('product_presentation_id') : null,
                (float) $request->input('quantity')
            );

            return back()->with('success', 'Producto agregado a la cuenta.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function removeItem(Request $request, Table $table, int $itemId)
    {
        $account = $table->activeAccount;
        if (!$account) {
            return back()->with('error', 'La mesa no tiene una cuenta abierta.');
        }

        try {
            $qty = $request->filled('qty') ? (float) $request->input('qty') : null;
            $this->accountService->removeItem($account, $itemId, $qty);
            return back()->with('success', 'Cuenta de mesa actualizada.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function transfer(Request $request, Table $table)
    {
        $request->validate([
            'destination_table_id' => 'required|exists:tables,id',
        ]);

        $account = $table->activeAccount;
        if (!$account) {
            return back()->with('error', 'La mesa no tiene una cuenta abierta.');
        }

        $destinationTable = Table::findOrFail($request->input('destination_table_id'));

        try {
            $this->accountService->transferAccount($account, $destinationTable);
            return redirect()->route('tables.show', $destinationTable->id)
                ->with('success', "Cuenta trasladada exitosamente a la mesa {$destinationTable->name}.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, Table $table)
    {
        $request->validate([
            'reason' => 'required|string|max:200',
        ]);

        $account = $table->activeAccount;
        if (!$account) {
            return back()->with('error', 'La mesa no tiene cuenta abierta.');
        }

        try {
            $this->accountService->cancelAccount($account, $request->input('reason'), auth()->user());
            return redirect()->route('tables.index')->with('success', "Cuenta cancelada y mesa liberada.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
