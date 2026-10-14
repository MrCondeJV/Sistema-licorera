<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Services\OfflineSyncService;
use Exception;
use Illuminate\Http\Request;

class OfflineSyncController extends Controller
{
    protected OfflineSyncService $syncService;

    public function __construct(OfflineSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    public function sync(Request $request)
    {
        $request->validate([
            'sync_token' => 'required|uuid',
            'operation_type' => 'required|string',
            'payload' => 'required|array',
        ]);

        $user = auth()->user() ?? $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        $session = CashSession::getActiveSession($user->id);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No hay una sesión de caja abierta en este momento para sincronizar ventas.',
            ], 422);
        }

        try {
            $result = $this->syncService->processOfflineSale(
                $request->input('payload'),
                $request->input('sync_token'),
                $user,
                $session
            );

            return response()->json([
                'success' => true,
                'status' => $result['status'],
                'message' => $result['message'],
                'sale_id' => $result['sale']?->id,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
