<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Weapon extends Model
{
    protected $fillable = [
        'serial_number',
        'brand',
        'model',
        'caliber',
        'magazine_capacity',
        'barrel_length_mm',
        'price',
        'status',
        'description',
    ];

    protected $casts = [
        'images' => 'array',
    ];


    public function movements(): HasMany
    {
        return $this->hasMany(WeaponMovement::class);
    }

    public function getStockAttribute(): int
    {
        $in = (int) $this->movements()->where('type', 'IN')->sum('quantity');
        $out = (int) $this->movements()->where('type', 'OUT')->sum('quantity');
        return $in - $out;
    }
}
