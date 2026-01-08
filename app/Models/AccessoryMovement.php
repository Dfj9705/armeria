<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class AccessoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'accessory_id',
        'type',
        'quantity',
        'unit_cost',
        'occurred_at',
        'reference',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'unit_cost' => 'decimal:2',
    ];

    public function accessory(): BelongsTo
    {
        return $this->belongsTo(Accessory::class, 'accessory_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $movement) {
            if ($movement->type === 'out') {
                $stock = $movement->accessory?->current_stock ?? 0;

                if ($movement->quantity > $stock) {
                    throw ValidationException::withMessages([
                        'quantity' => "No hay stock suficiente. Stock actual: {$stock}",
                    ]);
                }
            }
        });
    }
}
