<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeaponUnitMovement extends Model
{
    protected $table = 'weapon_unit_movements';

    protected $fillable = [
        'weapon_unit_id', 
        'type',
        'reference',
        'notes',
        'moved_at',
        'user_id',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(WeaponUnit::class, 'weapon_unit_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
