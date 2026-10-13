<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        $logs = $query->paginate(30);

        return view('audit.index', [
            'logs' => $logs,
        ]);
    }
}
