<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Accessory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'sku',
        'description',
        'unit_cost',
        'unit_price',
        'stock_min',
        'images',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'images' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AccessoryCategory::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(AccessoryMovement::class, 'accessory_id');
    }

    public function getCurrentStockAttribute(): int
    {
        $in = (int) $this->movements()->where('type', 'in')->sum('quantity');
        $out = (int) $this->movements()->where('type', 'out')->sum('quantity');

        return $in - $out;
    }

    public function hasLowStock(): bool
    {
        return $this->current_stock <= $this->stock_min;
    }
}
