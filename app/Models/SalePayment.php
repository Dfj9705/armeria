<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'amount',
        'method',
        'reference',
        'notes',
        'date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    protected static function booted()
    {
        static::creating(function ($payment) {
            $payment->created_by = auth()->id();
            $payment->updated_by = auth()->id();
        });

        static::updating(function ($payment) {
            $payment->updated_by = auth()->id();
        });
    }
}
