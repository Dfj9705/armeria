<style>
    table {
        width: 100%;
        border-collapse: collapse;
    }

    p {
        margin: 0;
    }
</style>

<table style="text-align: center; font-size: 8px; line-height: 1.5;">
    <tr>
        <td>
            <p>DOCUMENTO TRIBUTARIO ELECTRÓNICO</p>
            <p>FACTURA</p>
            <p>{{env('TEKRA_EMISOR_NOMBRE')}}</p>
            <p>{{env('TEKRA_EMISOR_NIT')}}</p>
        </td>
    </tr>
    <tr>
        <td>
            <p>Número de autorización</p>
            <p>{{ $sale->fel_uuid }}</p>
        </td>
    </tr>
    <tr>
        <td>
            <p>Serie: {{ $sale->fel_serie }}</p>
            <p>Numero: {{ $sale->fel_numero }}</p>
        </td>
    </tr>
    <tr>
        <td>
            <p>Fecha de emisión</p>
            <p>{{ $sale->fel_fecha_hora_emision }}</p>
        </td>
    </tr>
    <tr>
        <td>
            <p>Ref. Int.</p>
            <p>Venta: {{ $sale->id }}</p>
        </td>
    </tr>
    <tr>
        <td>
            <p>--------Datos del Emisor--------</p>
            <p>{{env('TEKRA_EMISOR_NOMBRE')}}</p>
            <p>NIT: {{env('TEKRA_EMISOR_NIT')}}</p>
            <p>{{env('TEKRA_EMISOR_DIRECCION')}}</p>
        </td>
    </tr>
    <tr>
        <td>
            <p>--------Datos del Cliente--------</p>
            <p>Nombre: {{ $sale->customer->name }}</p>
            <p>NIT: {{ $sale->customer->nit }}</p>
            <p>Dirección: {{ $sale->customer->address ?? 'CIUDAD'}}</p>
            <p>Teléfono: {{ $sale->customer->phone ?? 'N/A'}}</p>
        </td>
    </tr>
</table>
<table style="text-align: center; font-size: 8px; line-height: 1.5;">
    <tr>
        <td colspan="3">
            <p>--------Detalle de la Venta--------</p>
        </td>
    </tr>
    <tr>
        <td>CANT</td>
        <td>DESCRIPCION</td>
        <td>TOTAL</td>
    </tr>
    @php
        $total = 0;
        $totalDescuentos = 0;
    @endphp
    @foreach ($sale->items as $item)
        <tr>
            <td>{{ $item->qty }}</td>
            <td>{{ $item->description_snapshot }}</td>
            <td>{{ number_format($item->unit_price, 2) }}</td>
        </tr>
        @php
            $total += $item->unit_price;
        @endphp
        @if ($item->discount > 0)
            <tr>
                <td></td>
                <td>DESCUENTO</td>
                <td>-{{ number_format($item->discount, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3"></td>
            </tr>
            @php
                $totalDescuentos += $item->discount;
            @endphp
        @endif
    @endforeach
    <tr>
        <td colspan="3">--------------------------------</td>
    </tr>
    <tr style="font-weight: bold; text-align: left">
        <td colspan="2">TOTAL</td>
        <td>{{ number_format($total, 2) }}</td>
    </tr>
    <tr style="font-weight: bold; text-align: left">
        <td colspan="2">TOTAL DESCUENTOS</td>
        <td>-{{ number_format($totalDescuentos, 2) }}</td>
    </tr>
    <tr style="font-weight: bold; text-align: left">
        <td colspan="2">TOTAL NETO</td>
        <td>{{ number_format($total - $totalDescuentos, 2) }}</td>
    </tr>
    <tr>
        <td colspan="3">--------------------------------</td>
    </tr>
</table>
<table style="text-align: center; font-size: 8px; line-height: 1.5;">
    <tr>
        <td>
            <p>SUJETO A PAGOS TRIMESTRALES ISR</p>
        </td>
    </tr>
    <tr>
        <td>--------------------------------</td>
    </tr>
    <tr>
        <td>
            <p>Certificador: {{ $sale->fel_nombre_certificador }}</p>
            <p>Nit: {{ $sale->fel_nit_certificador }}</p>
            <p>Fecha de certificación: {{ $sale->fel_fecha_hora_certificacion }}</p>
            <p>Autorización: {{ $sale->fel_uuid }}</p>
        </td>
    </tr>
</table>
<table style="text-align: center; font-size: 8px; line-height: 1.5;">
    <tr>
        <td>
            <img src="data:image/png;base64,{{ $sale->fel_qr }}" alt="" style="width: 50%;">
        </td>
    </tr>
</table>