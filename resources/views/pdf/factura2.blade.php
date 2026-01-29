<style>
    * {
        box-sizing: border-box;
    }

    .row {
        width: 100%;
        clear: both;
        margin-bottom: 18px;
        margin-left: 8px;
    }

    .col {
        float: left;
        box-sizing: border-box;
    }

    .center {
        text-align: center;
    }

    .box {
        border: 1px solid #ccc;
        border-radius: 5px;
        padding: 18px;
        background-color: #fff;
    }

    p {
        margin: 0;
        line-height: 1.3;
        font-size: 12px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background-color: #000;
        color: #fff;
    }

    th,
    td {
        border: 1px solid #ccc;
        padding: 8px;
        text-align: center;
    }
</style>

<!-- ENCABEZADO -->
<div class="row">

    <!-- Logo -->
    <div class="col" style="width:8%">
        <img src="{{ public_path('escudo.png') }}" style="width:100%">
    </div>

    <!-- Emisor -->
    <div class="col center" style="width:55%">
        <p><strong>{{ env('TEKRA_EMISOR_NOMBRE') }}</strong></p>
        <p>NIT: {{ env('TEKRA_EMISOR_NIT') }}</p>
        <p>{{ env('TEKRA_EMISOR_DIRECCION') }}</p>
    </div>
    <div class="col" style="width:35%">
        <div class="box center">
            <p>DOCUMENTO TRIBUTARIO</p>
            <p>ELECTRÓNICO</p>
            <p><strong>FACTURA</strong></p>
            <br>
            <p>SERIE: {{ $sale->fel_serie }}</p>
            <p>NUMERO: {{ $sale->fel_numero }}</p>
        </div>
    </div>
</div>


<!-- CLIENTE -->
<div class="row">
    <div class="col" style="width:63%">
        <div class="box">
            <p><strong>Nombre:</strong> {{ $sale->customer->name }}</p>
            <p><strong>NIT:</strong> {{ $sale->customer->nit }}</p>
            <p><strong>Dirección:</strong> {{ $sale->customer->address ?? 'CIUDAD' }}</p>
            <p><strong>Teléfono:</strong> {{ $sale->customer->phone ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="col" style="width:35%">
        <div class="box center">
            <p><strong>Fecha de emisión</strong></p>
            <p>{{ $sale->fel_fecha_hora_emision }}</p>
            <p><strong>Moneda</strong></p>
            <p>GTQ</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col" style="width:98%">
        <div class="box">
            <table style="width: 100%; text-align: center;">
                <thead>
                    <tr>
                        <th>Cantidad</th>
                        <th>Descripción</th>
                        <th>Precio Unitario</th>
                        <th>Descuento</th>
                        <th>SubTotal</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $subtotal = 0;
                        $totalDescuentos = 0;
                    @endphp
                    @foreach ($sale->items as $item)
                        <tr>
                            <td>{{ $item->qty }}</td>
                            <td>{{ $item->description_snapshot }}</td>
                            <td>{{ number_format($item->unit_price, 2) }}</td>
                            <td>{{ number_format($item->discount, 2) }}</td>
                            <td>{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                        @php
                            $subtotal += $item->line_total;
                            $totalDescuentos += $item->discount;
                        @endphp
                    @endforeach
                    <tr>
                        <td colspan="4"><span style="font-weight: bold;">SUJETO A PAGOS TRIMESTRALES ISR</span></td>
                        <td>
                            <p style="font-weight: bold;">SUBTOTAL</p>
                            <p>{{ number_format($subtotal, 2) }}</p>
                            <p style="font-weight: bold;">TOTAL DESCUENTOS</p>
                            <p>-{{ number_format($totalDescuentos, 2) }}</p>
                            <p style="font-weight: bold;">TOTAL</p>
                            <p>{{ number_format($subtotal - $totalDescuentos, 2) }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4"><span style="font-weight: bold;">RESUMEN DE IMPUESTOS</span></td>
                        <td>
                            <p style="font-weight: bold;">IVA 12%</p>
                            <p>{{ number_format($sale->tax, 2) }}</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row">
    <div class="col" style="width:98%">
        <div class="box">
            <p><span style="font-weight: bold;">No. de Autorización:</span> {{ $sale->fel_uuid }}</p>
            <p><span style="font-weight: bold;">Certificador:</span> {{ $sale->fel_nombre_certificador }}</p>
            <p><span style="font-weight: bold;">Nit:</span> {{ $sale->fel_nit_certificador }}</p>
            <p><span style="font-weight: bold;">Fecha de certificación:</span> {{ $sale->fel_fecha_hora_certificacion }}
            </p>
        </div>
    </div>
</div>

<div class="row center">
    <div class="col" style="width:25%; max-height: 100px;">
        <img src="data:image/png;base64,{{ $sale->fel_qr }}" alt="" style="width: 100%;">
    </div>
</div>