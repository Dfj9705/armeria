<?php

namespace App\Services\Sales;

use App\Models\Sale;
use App\Models\WeaponUnit;
use App\Models\WeaponUnitMovement;
use App\Models\Ammo;
use App\Models\AmmoMovement;
use App\Models\Accessory;
use App\Models\AccessoryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmSale
{
    public function handle(Sale $sale, int $userId): Sale
    {
        if ($sale->status !== 'draft') {
            throw ValidationException::withMessages(['sale' => 'La venta no está en borrador.']);
        }

        $sale->load('items.sellable');

        if ($sale->items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'La venta no tiene ítems.']);
        }

        return DB::transaction(function () use ($sale, $userId) {
            $subtotal = 0;

            foreach ($sale->items as $item) {
                $sellable = $item->sellable;

                $qty = (float) $item->qty;
                $unitPrice = (float) $item->unit_price;

                if ($qty <= 0) {
                    throw ValidationException::withMessages(['items' => 'Cantidad inválida.']);
                }

                $lineTotal = round($qty * $unitPrice, 2);
                $item->line_total = $lineTotal;
                $item->save();

                $subtotal += $lineTotal;

                // ====== ARMAS (WeaponUnit) ======
                if ($sellable instanceof WeaponUnit) {
                    if ($qty != 1.0) {
                        throw ValidationException::withMessages(['items' => 'Un arma debe tener cantidad 1.']);
                    }

                    if ($sellable->status !== 'in_stock') {
                        throw ValidationException::withMessages([
                            'items' => "Arma serie {$sellable->serial_number} no disponible."
                        ]);
                    }

                    WeaponUnitMovement::create([
                        'weapon_unit_id' => $sellable->id,
                        'type' => 'OUT',
                        'reference' => 'SALE:' . $sale->id,
                        'moved_at' => now(),
                        'user_id' => $userId,
                    ]);

                    $sellable->update(['status' => 'sold']);
                }

                // ====== MUNICIÓN (Ammo) ======
                elseif ($sellable instanceof Ammo) {
                    $meta = $item->meta ?? [];
                    $boxes = $meta['boxes'] ?? null;
                    $rounds = $meta['rounds'] ?? null;

                    if ($boxes === null && $rounds === null) {
                        throw ValidationException::withMessages([
                            'items' => 'Munición debe indicar meta.boxes o meta.rounds.'
                        ]);
                    }

                    // Aquí puedes meter tu validación de saldo (sumatoria IN-OUT)
                    AmmoMovement::create([
                        'ammo_id' => $sellable->id,
                        'type' => 'OUT',
                        'boxes' => $boxes,
                        'rounds' => $rounds,
                        'unit_cost_box' => null,
                        'reference' => 'SALE:' . $sale->id,
                        'moved_at' => now(),
                        'user_id' => $userId,
                    ]);
                }

                // ====== ACCESORIOS (Accessory) ======
                elseif ($sellable instanceof Accessory) {
                    // validar saldo con tu lógica actual si deseas
                    AccessoryMovement::create([
                        'accessory_id' => $sellable->id,
                        'type' => 'out',
                        'quantity' => $qty,
                        'occurred_at' => now(),
                        'reference' => 'SALE:' . $sale->id,
                        'user_id' => $userId,
                    ]);
                } else {
                    throw ValidationException::withMessages(['items' => 'Ítem no soportado.']);
                }
            }

            $tax = round($subtotal * 0.12, 2);
            $total = round($subtotal + $tax, 2);

            $sale->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            return $sale->fresh(['items']);
        });
    }
}
