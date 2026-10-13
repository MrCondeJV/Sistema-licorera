<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::today()->startOfMonth();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::today();

        $userId = $request->input('user_id') ? (int) $request->input('user_id') : null;
        $paymentMethodId = $request->input('payment_method_id') ? (int) $request->input('payment_method_id') : null;

        $report = $this->reportService->getFilteredSalesReport($startDate, $endDate, $userId, $paymentMethodId);

        $employees = User::where('is_active', true)->orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();

        return view('reports.index', [
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'userId' => $userId,
            'paymentMethodId' => $paymentMethodId,
            'employees' => $employees,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::today()->startOfMonth();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::today();

        $userId = $request->input('user_id') ? (int) $request->input('user_id') : null;
        $paymentMethodId = $request->input('payment_method_id') ? (int) $request->input('payment_method_id') : null;

        $report = $this->reportService->getFilteredSalesReport($startDate, $endDate, $userId, $paymentMethodId);

        $pdf = Pdf::loadView('reports.pdf', [
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tenant' => app(\App\Services\TenantContext::class)->getTenant(),
        ]);

        return $pdf->download("reporte_ventas_{$startDate->format('Ymd')}_{$endDate->format('Ymd')}.pdf");
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::today()->startOfMonth();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::today();

        $userId = $request->input('user_id') ? (int) $request->input('user_id') : null;
        $paymentMethodId = $request->input('payment_method_id') ? (int) $request->input('payment_method_id') : null;

        $report = $this->reportService->getFilteredSalesReport($startDate, $endDate, $userId, $paymentMethodId);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"reporte_ventas_{$startDate->format('Ymd')}_{$endDate->format('Ymd')}.csv\"",
        ];

        return new StreamedResponse(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

            // Header row
            fputcsv($handle, ['Número Venta', 'Fecha', 'Cajero', 'Subtotal', 'Descuento', 'Impuesto', 'Total', 'Métodos de Pago', 'Costo Estimado', 'Margen Bruto']);

            foreach ($report['sales'] as $sale) {
                $methods = $sale->payments->map(fn($p) => $p->paymentMethod->name . ' ($' . number_format($p->amount, 0, ',', '.') . ')')->implode(', ');
                $cost = $sale->calculateTotalCost();
                $profit = $sale->total - $cost;

                fputcsv($handle, [
                    $sale->sale_number,
                    $sale->sold_at->format('Y-m-d H:i:s'),
                    $sale->user->name,
                    $sale->subtotal,
                    $sale->discount_total,
                    $sale->tax_total,
                    $sale->total,
                    $methods,
                    $cost,
                    $profit,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
