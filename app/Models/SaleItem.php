<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'sellable_type',
        'sellable_id',
        'qty',
        'unit_price',
        'discount',
        'line_total',
        'description_snapshot',
        'uom_snapshot',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function sellable(): MorphTo
    {
        return $this->morphTo();
    }
}
