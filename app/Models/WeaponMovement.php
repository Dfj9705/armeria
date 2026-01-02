<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeaponMovement extends Model
{
    protected $fillable = [
        'weapon_id',
        'type',
        'quantity',
        'unit_cost',
        'reference',
        'notes',
        'moved_at',
        'user_id',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
    ];

    public function weapon(): BelongsTo
    {
        return $this->belongsTo(Weapon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

