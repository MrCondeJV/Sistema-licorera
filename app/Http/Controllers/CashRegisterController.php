<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\PaymentMethod;
use App\Services\CashRegisterService;
use Exception;
use Illuminate\Http\Request;

class CashRegisterController extends Controller
{
    protected CashRegisterService $cashService;

    public function __construct(CashRegisterService $cashService)
    {
        $this->cashService = $cashService;
    }

    public function index()
    {
        $user = auth()->user();
        $registers = CashRegister::where('is_active', true)->get();

        $activeSession = CashSession::getActiveSession($user->id);

        $summary = $activeSession ? $activeSession->calculateCashSummary() : null;

        return view('cash.index', [
            'registers' => $registers,
            'activeSession' => $activeSession,
            'summary' => $summary,
        ]);
    }

    public function open(Request $request)
    {
        $request->validate([
            'cash_register_id' => 'required|exists:cash_registers,id',
            'opening_amount' => 'required|numeric|min:0',
        ]);

        $register = CashRegister::findOrFail($request->input('cash_register_id'));

        try {
            $session = $this->cashService->openSession(
                $register,
                auth()->user(),
                (float) $request->input('opening_amount')
            );

            return redirect()->route('cash.index')->with('success', "Turno de caja abierto correctamente con base de $" . number_format($session->opening_amount, 0, ',', '.') . " COP.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function close(Request $request, CashSession $session)
    {
        $request->validate([
            'counted_cash' => 'required|numeric|min:0',
            'closing_notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->cashService->closeSession(
                $session,
                (float) $request->input('counted_cash'),
                $request->input('closing_notes'),
                auth()->user()
            );

            return redirect()->route('cash.history')->with('success', "Turno de caja cerrado exitosamente.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function movement(Request $request, CashSession $session)
    {
        $request->validate([
            'movement_type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:200',
        ]);

        try {
            $this->cashService->recordMovement(
                $session,
                $request->input('movement_type'),
                (float) $request->input('amount'),
                $request->input('reason'),
                auth()->user()
            );

            $label = $request->input('movement_type') === 'in' ? 'Ingreso' : 'Retiro';
            return back()->with('success', "{$label} de efectivo registrado correctamente.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function history()
    {
        $sessions = CashSession::where('status', 'closed')
            ->with(['user', 'cashRegister'])
            ->latest('closed_at')
            ->paginate(20);

        return view('cash.history', [
            'sessions' => $sessions,
        ]);
    }
}
