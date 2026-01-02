<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Weapon extends Model
{
    protected $fillable = [
        'serial_number',
        'brand_id',
        'brand_model_id',
        'caliber',
        'magazine_capacity',
        'barrel_length_mm',
        'price',
        'status',
        'description',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];


    public function movements(): HasMany
    {
        return $this->hasMany(WeaponMovement::class);
    }

    public function units()
    {
        return $this->hasMany(\App\Models\WeaponUnit::class);
    }

    public function getStockAttribute(): int
    {
        return $this->units()->where('status', 'IN_STOCK')->count();
    }


    public function brand()
    {
        return $this->belongsTo(\App\Models\Brand::class);
    }

    public function brandModel()
    {
        return $this->belongsTo(\App\Models\BrandModel::class, 'brand_model_id');
    }

    public function unitMovements()
    {
        return $this->hasManyThrough(
            WeaponUnitMovement::class,
            WeaponUnit::class,
            'weapon_id',        // FK en weapon_units
            'weapon_unit_id',   // FK en movements
            'id',
            'id'
        );
    }

}
