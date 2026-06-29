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
        'fel_fecha_hora_anulacion',
        'fel_motivo_anulacion',
        'created_by',
        'updated_by',
        'branch_id',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected $appends = [
        'total_paid',
        'pending_amount',
        'is_paid',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getPendingAmountAttribute(): float
    {
        return max(0, (float) $this->total - $this->total_paid);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->pending_amount <= 0;
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
