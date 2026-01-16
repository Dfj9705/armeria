<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeaponUnit extends Model
{
    use HasFactory;
    protected $fillable = [
        'weapon_id',
        'serial_number',
        'status',
        'purchase_cost',
        'notes',
        'possesion_number',
    ];

    public function weapon()
    {
        return $this->belongsTo(Weapon::class);
    }

    public function movements()
    {
        return $this->hasMany(WeaponUnitMovement::class);
    }

}
