<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 10px;
            color: #111;
            width: 220px;
            margin: 0 auto;
            padding: 14px 10px;
        }
        .header { text-align: center; margin-bottom: 6px; }
        .header img { max-width: 48px; max-height: 48px; margin-bottom: 4px; }
        .header h1 { font-size: 15px; color: {{ $empresa->color_primario ?? '#166534' }}; margin: 0; letter-spacing: 1px; font-family: 'DejaVu Serif', serif; }
        .header .eslogan { font-size: 8px; color: #888; margin-top: 1px; }
        .badge { border: 1px solid {{ $empresa->color_primario ?? '#166534' }}; color: {{ $empresa->color_primario ?? '#166534' }}; font-size: 8px; padding: 1px 5px; display: inline-block; margin-top: 3px; }
        .dashed { border-top: 1px dashed #999; margin: 6px 0; }
        table.fila-tabla { width: 100%; border-collapse: collapse; }
        table.fila-tabla td { padding: 1px 0; vertical-align: top; }
        table.fila-tabla td.derecha { text-align: right; }
        .prod-nombre { font-weight: bold; }
        .prod-precio { color: #666; font-size: 9px; }
        .totales td { padding: 1px 0; }
        .total-final { font-weight: bold; font-size: 12px; }
        .footer { text-align: center; margin-top: 8px; }
        .barcode { text-align: center; margin-top: 6px; }
        .barcode img {
            max-width: 90% !important;
            width: auto !important;
            height: auto !important;
            display: block !important;
            margin: 0 auto !important;
        }
    </style>
</head>
<body>
    <div class="header">
        @if ($empresa && $empresa->logo_path)
            <img src="{{ storage_path('app/public/' . $empresa->logo_path) }}">
        @endif
        <h1>{{ $empresa->nombre_licoreria ?? 'BottleTrack' }}</h1>
        @if ($empresa && $empresa->eslogan)
            <div class="eslogan">{{ $empresa->eslogan }}</div>
        @endif
        <div class="badge">{{ $venta->estado_activa ? 'EMITIDA' : 'ANULADA' }}</div>
        <div>#{{ $venta->numero_factura }}</div>
        <div>{{ \Carbon\Carbon::parse($venta->fecha)->format('d/m/Y') }} · {{ $venta->created_at->format('H:i') }}</div>
    </div>

    <div class="dashed"></div>

    <table class="fila-tabla">
        <tr>
            <td><strong>CLIENTE</strong></td>
            <td class="derecha"><strong>ATENDIDO POR</strong></td>
        </tr>
        <tr>
            <td>{{ $venta->cliente_nombre ?? 'Consumidor final' }}</td>
            <td class="derecha">{{ $venta->usuario->name }}</td>
        </tr>
    </table>

    <div class="dashed"></div>

    @foreach ($venta->detalles as $detalle)
        <table class="fila-tabla">
            <tr>
                <td class="prod-nombre">{{ $detalle->cantidad }}× {{ $detalle->producto->nombre }}</td>
                <td class="derecha">{{ $empresa->moneda ?? '$' }}{{ number_format($detalle->subtotal, 2) }}</td>
            </tr>
        </table>
        <div class="prod-precio">{{ $empresa->moneda ?? '$' }}{{ number_format($detalle->precio_unitario, 2) }} c/u</div>
    @endforeach

    <div class="dashed"></div>

    <table class="totales" width="100%">
        <tr><td>Subtotal</td><td align="right">{{ $empresa->moneda ?? '$' }}{{ number_format($venta->subtotal, 2) }}</td></tr>
        <tr><td>Descuento</td><td align="right">-{{ $empresa->moneda ?? '$' }}{{ number_format($venta->descuento, 2) }}</td></tr>
        <tr><td>IVA (15%)</td><td align="right">{{ $empresa->moneda ?? '$' }}{{ number_format($venta->impuesto, 2) }}</td></tr>
    </table>

    <div class="dashed"></div>

    <table class="totales total-final" width="100%">
        <tr><td>TOTAL</td><td align="right">{{ $empresa->moneda ?? '$' }}{{ number_format($venta->total, 2) }}</td></tr>
    </table>

    <div class="dashed"></div>

    <div class="barcode">
        {!! DNS1D::getBarcodeHTML($venta->numero_factura, 'C128', 0.9, 40) !!}
        <div style="font-size:9px; letter-spacing:1px; margin-top:2px;">{{ $venta->numero_factura }}</div>
    </div>

    <div class="footer">¡GRACIAS POR SU COMPRA!</div>
</body>
</html>