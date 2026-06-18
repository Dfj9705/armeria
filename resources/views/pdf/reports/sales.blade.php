<!DOCTYPE html>
<html>

    <head>
        <meta charset="utf-8">

        <style>
            body {
                font-family: sans-serif;
                font-size: 10px;
                color: #222;
            }

            h1,
            h2,
            h3,
            h4 {
                margin: 0;
            }

            .header {
                margin-bottom: 15px;
            }

            .header p {
                margin: 2px 0;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            .summary {
                margin-top: 15px;
                margin-bottom: 20px;
            }

            .summary th,
            .summary td {
                border: 1px solid #000;
                padding: 5px;
            }

            .sale-header {
                margin-top: 20px;
                margin-bottom: 5px;
                background: #efefef;
                padding: 5px;
            }

            .detail-table th {
                background: #d9d9d9;
            }

            .detail-table th,
            .detail-table td {
                border: 1px solid #000;
                padding: 4px;
            }

            .right {
                text-align: right;
            }

            .center {
                text-align: center;
            }

            .totals {
                margin-top: 5px;
                width: 40%;
                margin-left: auto;
            }

            .totals th,
            .totals td {
                border: 1px solid #000;
                padding: 4px;
            }

            .page-break {
                page-break-after: always;
            }
        </style>
    </head>

    <body>

        <div class="header">
            <h1>Reporte de Ventas</h1>

            <p>
                Período:
                {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}
                al
                {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
            </p>

            <p>
                Estado:
                {{ $status }}
            </p>

            <p>
                Generado:
                {{ now()->format('d/m/Y H:i:s') }}
            </p>
        </div>

        @php
            $totalSales = $sales->sum('total');
            $totalPaid = $sales->sum('total_paid');
            $totalPending = $sales->sum('pending_amount');
        @endphp

        <table class="summary">
            <tr>
                <th>Total vendido</th>
                <th>Total cobrado</th>
                <th>Total pendiente</th>
            </tr>
            <tr>
                <td class="right">Q {{ number_format($totalSales, 2) }}</td>
                <td class="right">Q {{ number_format($totalPaid, 2) }}</td>
                <td class="right">Q {{ number_format($totalPending, 2) }}</td>
            </tr>
        </table>

        @foreach ($sales as $sale)

            <div class="sale-header">
                <strong>Venta #{{ $sale->id }}</strong>
            </div>

            <table style="margin-bottom:10px;">
                <tr>
                    <td width="20%"><strong>Fecha:</strong></td>
                    <td width="30%">
                        {{ $sale->created_at?->format('d/m/Y H:i') }}
                    </td>

                    <td width="20%"><strong>Estado:</strong></td>
                    <td width="30%">
                        {{ strtoupper($sale->status) }}
                    </td>
                </tr>

                <tr>
                    <td><strong>Cliente:</strong></td>
                    <td>
                        {{ $sale->customer?->name }}
                    </td>

                    <td><strong>NIT:</strong></td>
                    <td>
                        {{ $sale->customer?->nit }}
                    </td>
                </tr>

                <tr>
                    <td><strong>Serie FEL:</strong></td>
                    <td>
                        {{ $sale->fel_serie ?? '-' }}
                    </td>

                    <td><strong>Número FEL:</strong></td>
                    <td>
                        {{ $sale->fel_numero ?? '-' }}
                    </td>
                </tr>
            </table>

            <table class="detail-table">
                <thead>
                    <tr>
                        <th width="35%">Descripción</th>
                        <th width="10%">Autorización</th>
                        <th width="10%">Unidad</th>
                        <th width="10%">Cantidad</th>
                        <th width="10%">Precio</th>
                        <th width="10%">Desc.</th>
                        <th width="15%">Total</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach ($sale->items as $item)

                        <tr>
                            <td>
                                {{ $item->description_snapshot }}
                            </td>

                            <td class="center">
                                {{ $item->authorization_number ?? '-' }}
                            </td>

                            <td class="center">
                                {{ $item->uom_snapshot }}
                            </td>

                            <td class="right">
                                {{ number_format($item->qty, 2) }}
                            </td>

                            <td class="right">
                                Q {{ number_format($item->unit_price, 2) }}
                            </td>

                            <td class="right">
                                Q {{ number_format($item->discount, 2) }}
                            </td>

                            <td class="right">
                                Q {{ number_format($item->line_total, 2) }}
                            </td>
                        </tr>

                    @endforeach

                </tbody>
            </table>

            <table class="totals">
                <tr>
                    <th>Total venta</th>
                    <td class="right">
                        Q {{ number_format($sale->total, 2) }}
                    </td>
                </tr>

                <tr>
                    <th>Total pagado</th>
                    <td class="right">
                        Q {{ number_format($sale->total_paid, 2) }}
                    </td>
                </tr>

                <tr>
                    <th>Saldo pendiente</th>
                    <td class="right">
                        Q {{ number_format($sale->pending_amount, 2) }}
                    </td>
                </tr>
            </table>

        @endforeach

    </body>

</html>