<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante #{{ $sale->sale_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace;
        }
        body {
            background-color: #f3f4f6;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .ticket {
            background: #ffffff;
            width: 80mm;
            padding: 5mm;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            font-size: 12px;
            color: #000000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .border-t { border-top: 1px dashed #000; }
        .border-b { border-bottom: 1px dashed #000; }
        .my-2 { margin-top: 8px; margin-bottom: 8px; }
        .py-1 { padding-top: 4px; padding-bottom: 4px; }
        .table-items { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .table-items th { border-bottom: 1px dashed #000; padding: 4px 0; font-size: 11px; }
        .table-items td { padding: 4px 0; font-size: 11px; }
        .no-print {
            margin-bottom: 15px;
            text-align: center;
        }
        .btn-print {
            background: #000;
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        @media print {
            body { background: transparent; padding: 0; }
            .ticket { width: 100%; box-shadow: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div>
        <div class="no-print">
            <button class="btn-print" onclick="window.print()">IMPRIMIR COMPROBANTE</button>
        </div>

        <div class="ticket">
            <!-- Header -->
            <div class="text-center">
                <h1 style="font-size: 16px; font-weight: 900; margin-bottom: 4px;">{{ $sale->tenant->name ?? 'LA NOCHE' }}</h1>
                <p>{{ $sale->tenant->tax_id ?? 'NIT: 901.458.789-3' }}</p>
                <p>{{ $sale->tenant->address ?? 'Medellín, Colombia' }}</p>
                <p>Tel: {{ $sale->tenant->phone ?? '310 456 7890' }}</p>
            </div>

            <div class="border-t border-b my-2 py-1">
                <p><strong>COMPROBANTE:</strong> {{ $sale->sale_number }}</p>
                <p><strong>FECHA:</strong> {{ $sale->sold_at->format('d/m/Y h:i A') }}</p>
                <p><strong>CAJERO:</strong> {{ $sale->user->name }}</p>
                @if($sale->account && $sale->account->table)
                    <p><strong>MESA:</strong> {{ $sale->account->table->name }}</p>
                @endif
            </div>

            <!-- Items -->
            <table class="table-items">
                <thead>
                    <tr>
                        <th style="text-align: left;">CANT</th>
                        <th style="text-align: left;">PRODUCTO</th>
                        <th style="text-align: right;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                        <tr>
                            <td style="vertical-align: top;">{{ rtrim(rtrim($item->quantity, '0'), '.') }}</td>
                            <td style="vertical-align: top;">
                                {{ $item->product_name_snapshot }}
                                @if($item->presentation_name_snapshot !== 'Unidad')
                                    <div style="font-size: 9px; color: #555;">[{{ $item->presentation_name_snapshot }}]</div>
                                @endif
                            </td>
                            <td style="text-align: right; vertical-align: top;">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Totals -->
            <div class="border-t my-2 py-1" style="font-size: 13px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                    <span>SUBTOTAL:</span>
                    <span>${{ number_format($sale->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($sale->discount_total > 0)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                        <span>DESCUENTO:</span>
                        <span>-${{ number_format($sale->discount_total, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 15px; margin-top: 4px; border-top: 1px dashed #000; padding-top: 4px;">
                    <span>TOTAL:</span>
                    <span>${{ number_format($sale->total, 0, ',', '.') }} COP</span>
                </div>
            </div>

            <!-- Payments -->
            <div class="border-t my-2 py-1">
                <p class="font-bold" style="margin-bottom: 4px;">MEDIOS DE PAGO:</p>
                @foreach($sale->payments as $payment)
                    <div style="display: flex; justify-content: space-between;">
                        <span>{{ $payment->paymentMethod->name }}:</span>
                        <span>${{ number_format($payment->amount, 0, ',', '.') }}</span>
                    </div>
                    @if($payment->paymentMethod->is_cash && $payment->received_amount > $payment->amount)
                        <div style="display: flex; justify-content: space-between; font-size: 10px; color: #444;">
                            <span>Recibido: ${{ number_format($payment->received_amount, 0, ',', '.') }}</span>
                            <span>Cambio: ${{ number_format($payment->change_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Footer Message -->
            <div class="text-center border-t my-2 py-1" style="font-size: 11px;">
                <p>{{ $sale->tenant->getSetting('receipt_message', '¡Gracias por visitarnos!') }}</p>
                <p style="font-size: 9px; color: #666; margin-top: 6px;">Comprobante de venta interno para control operacional.</p>
            </div>
        </div>
    </div>
</body>
</html>
