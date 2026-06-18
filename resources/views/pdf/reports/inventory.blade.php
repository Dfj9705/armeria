<h2>Reporte de Inventario</h2>
<p>Generado: {{ now()->format('d/m/Y H:i') }}</p>

<h3>Armas</h3>
<table width="100%" border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Tipo</th>
            <th>Calibre</th>
            <th>Stock</th>
            <th>Precio</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($weapons as $weapon)
            <tr>
                <td>{{ $weapon->brand?->name }}</td>
                <td>{{ $weapon->brandModel?->name }}</td>
                <td>{{ $weapon->type?->name }}</td>
                <td>{{ $weapon->caliber?->name }}</td>
                <td>{{ $weapon->stock }}</td>
                <td>Q {{ number_format($weapon->price, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h3>Municiones</h3>
<table width="100%" border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Marca</th>
            <th>Calibre</th>
            <th>Cajas</th>
            <th>Tiros sueltos</th>
            <th>Total tiros</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($ammos as $ammo)
            <tr>
                <td>{{ $ammo->name }}</td>
                <td>{{ $ammo->brand?->name }}</td>
                <td>{{ $ammo->caliber?->name }}</td>
                <td>{{ $ammo->stock_boxes }}</td>
                <td>{{ $ammo->stock_loose_rounds }}</td>
                <td>{{ $ammo->stock_rounds }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h3>Accesorios</h3>
<table width="100%" border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>Producto</th>
            <th>Categoría</th>
            <th>Marca</th>
            <th>Stock</th>
            <th>Precio</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($accessories as $accessory)
            <tr>
                <td>{{ $accessory->name }}</td>
                <td>{{ $accessory->category?->name }}</td>
                <td>{{ $accessory->brand?->name }}</td>
                <td>{{ $accessory->current_stock }}</td>
                <td>Q {{ number_format($accessory->unit_price, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>