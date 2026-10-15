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
        <div class="no-print" style="display: flex; gap: 8px; justify-content: center; align-items: center; margin-bottom: 15px; flex-wrap: wrap;">
            <button class="btn-print" onclick="window.print()" style="display: inline-flex; align-items: center; gap: 6px;">
                <svg style="width: 15px; height: 15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                IMPRIMIR COMPROBANTE
            </button>
            <a href="{{ $sale->getWhatsappUrl() }}" target="_blank" style="background: #0f766e; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                <svg style="width: 15px; height: 15px;" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.976.532 1.943.792 3.149.792 3.182 0 5.767-2.587 5.767-5.766.001-3.182-2.585-5.734-5.767-5.734zm0-2c4.28 0 7.767 3.486 7.767 7.734 0 4.281-3.487 7.766-7.767 7.766-1.332 0-2.585-.34-3.69-.935L4 20l1.272-4.225A7.697 7.697 0 0 1 4.264 11.938c0-4.248 3.487-7.766 7.767-7.766z"/></svg>
                ENVIAR POR WHATSAPP
            </a>
        </div>

        <div class="ticket">
            <!-- Header -->
            <div class="text-center">
                <h1 style="font-size: 16px; font-weight: 900; margin-bottom: 4px;">{{ $sale->tenant->name ?? 'LICORERA POS' }}</h1>
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
                <p class="font-bold" style="margin-bottom: 4px;">ESTADO DE PAGO:</p>
                @if($sale->payments->isEmpty())
                    <div style="display: flex; justify-content: space-between; font-weight: bold; color: #b45309;">
                        <span>CARGADO A CUENTA:</span>
                        <span>${{ number_format($sale->total, 0, ',', '.') }}</span>
                    </div>
                    @if($sale->customer)
                        <div style="font-size: 11px; margin-top: 3px; color: #333;">
                            <span>CLIENTE: {{ $sale->customer->name }}</span>
                        </div>
                    @endif
                    <div style="font-size: 10px; color: #666; font-style: italic; margin-top: 2px;">
                        (Pendiente de cobro / Cargado a cartera)
                    </div>
                @else
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
                @endif
            </div>

            <!-- Footer Message -->
            <div class="text-center border-t my-2 py-1" style="font-size: 11px;">
                <p>{{ $sale->tenant->getSetting('receipt_message', '¡Gracias por visitarnos!') }}</p>
                <p style="font-size: 9px; color: #666; margin-top: 6px;">Comprobante de venta interno para control operacional.</p>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            if (params.get('autoprint') === '1') {
                setTimeout(() => {
                    window.print();
                }, 350);
            }
        });
    </script>
</body>
</html>
