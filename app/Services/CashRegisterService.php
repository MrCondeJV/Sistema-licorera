<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{
    /**
     * Open a cash register shift/session.
     */
    public function openSession(CashRegister $register, User $user, float $openingAmount): CashSession
    {
        return DB::transaction(function () use ($register, $user, $openingAmount) {
            // Check if register already has an open session
            $existing = CashSession::where('cash_register_id', $register->id)
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new Exception("La caja '{$register->name}' ya tiene una sesión abierta activa por el usuario {$existing->user->name}.");
            }

            $session = CashSession::create([
                'tenant_id' => $register->tenant_id,
                'branch_id' => $register->branch_id ?? app(TenantContext::class)->getBranchId(),
                'cash_register_id' => $register->id,
                'user_id' => $user->id,
                'opening_amount' => $openingAmount,
                'expected_cash' => $openingAmount,
                'status' => 'open',
                'opened_at' => now(),
            ]);

            AuditService::log(
                'cash_session_opened',
                'CashSession',
                $session->id,
                null,
                ['opening_amount' => $openingAmount, 'register' => $register->name],
                "Apertura de turno de caja"
            );

            return $session;
        });
    }

    /**
     * Close a cash register shift/session with physical blind count and difference calculation.
     */
    public function closeSession(
        CashSession $session,
        float $countedCash,
        ?string $notes,
        User $user
    ): CashSession {
        if (!$session->isOpen()) {
            throw new Exception("La sesión de caja ya se encuentra cerrada.");
        }

        return DB::transaction(function () use ($session, $countedCash, $notes, $user) {
            $summary = $session->calculateCashSummary();
            $expectedCash = $summary['expected_cash'];
            $difference = $countedCash - $expectedCash;

            if (abs($difference) > 0 && empty(trim((string)$notes))) {
                throw new Exception("Existe un descuadre de caja ($" . number_format($difference, 0, ',', '.') . "). Es obligatorio ingresar una observación justificando la diferencia.");
            }

            $session->expected_cash = $expectedCash;
            $session->counted_cash = $countedCash;
            $session->difference = $difference;
            $session->status = 'closed';
            $session->closing_notes = $notes;
            $session->closed_at = now();
            $session->save();

            AuditService::log(
                'cash_session_closed',
                'CashSession',
                $session->id,
                ['opening' => $session->opening_amount],
                [
                    'expected' => $expectedCash,
                    'counted' => $countedCash,
                    'difference' => $difference,
                    'notes' => $notes,
                ],
                "Cierre y arqueo de turno de caja"
            );

            return $session;
        });
    }

    /**
     * Record cash in / out movement during shift.
     */
    public function recordMovement(
        CashSession $session,
        string $type,
        float $amount,
        string $reason,
        User $user,
        ?int $paymentMethodId = null
    ): CashMovement {
        if (!$session->isOpen()) {
            throw new Exception("No se pueden registrar movimientos en una sesión de caja cerrada.");
        }

        if ($amount <= 0) {
            throw new Exception("El valor debe ser superior a cero.");
        }

        if (!in_array($type, ['in', 'out', 'expense'])) {
            throw new Exception("Tipo de movimiento inválido.");
        }

        return DB::transaction(function () use ($session, $type, $amount, $reason, $user, $paymentMethodId) {
            $movement = CashMovement::create([
                'tenant_id' => $session->tenant_id,
                'cash_session_id' => $session->id,
                'user_id' => $user->id,
                'movement_type' => $type,
                'amount' => $amount,
                'payment_method_id' => $paymentMethodId,
                'reason' => $reason,
            ]);

            AuditService::log(
                "cash_movement_{$type}",
                'CashMovement',
                $movement->id,
                null,
                ['amount' => $amount, 'reason' => $reason],
                "Movimiento manual de caja"
            );

            return $movement;
        });
    }
}
