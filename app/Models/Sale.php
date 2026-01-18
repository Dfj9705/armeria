<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'status',
        'subtotal',
        'tax',
        'total',
        'confirmed_at',
        'fel_uuid',
        'fel_serie',
        'fel_numero',
        'fel_status',
        'fel_fecha_hora_emision',
        'fel_fecha_hora_certificacion',
        'fel_nombre_receptor',
        'fel_estado_documento',
        'fel_nit_certificador',
        'fel_nombre_certificador',
        'fel_qr',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
