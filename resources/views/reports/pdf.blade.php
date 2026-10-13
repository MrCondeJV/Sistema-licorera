<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .subtitle { font-size: 12px; color: #555; }
        .summary-box { background: #f3f4f6; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10px; }
        .table th, .table td { border: 1px solid #ddd; padding: 6px; }
        .table th { background-color: #0b0f19; color: #ffffff; text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $tenant->name ?? 'LICORERA POS' }}</div>
        <div class="subtitle">Reporte de Ventas y Rendimiento Comercial</div>
        <div style="font-size: 10px; margin-top: 5px;">Período: {{ $startDate->format('d/m/Y') }} al {{ $endDate->format('d/m/Y') }}</div>
    </div>

    <div class="summary-box">
        <table style="width: 100%;">
            <tr>
                <td><strong>Total Ventas:</strong> ${{ number_format($report['total_sales'], 0, ',', '.') }} COP</td>
                <td><strong>Costo Estimado (COGS):</strong> ${{ number_format($report['cogs'], 0, ',', '.') }} COP</td>
            </tr>
            <tr>
                <td><strong>Utilidad Bruta:</strong> ${{ number_format($report['gross_profit'], 0, ',', '.') }} COP</td>
                <td><strong>Margen Bruto:</strong> {{ $report['profit_margin'] }}%</td>
            </tr>
            <tr>
                <td><strong>Transacciones:</strong> {{ $report['count'] }}</td>
                <td><strong>Descuentos:</strong> ${{ number_format($report['total_discounts'], 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Venta #</th>
                <th>Fecha</th>
                <th>Cajero</th>
                <th class="text-right">Total</th>
                <th class="text-right">Costo</th>
                <th class="text-right">Margen</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['sales'] as $sale)
                @php
                    $cost = $sale->calculateTotalCost();
                    $profit = $sale->total - $cost;
                @endphp
                <tr>
                    <td>{{ $sale->sale_number }}</td>
                    <td>{{ $sale->sold_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $sale->user->name }}</td>
                    <td class="text-right font-bold">${{ number_format($sale->total, 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($cost, 0, ',', '.') }}</td>
                    <td class="text-right font-bold" style="color: #059669;">${{ number_format($profit, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
