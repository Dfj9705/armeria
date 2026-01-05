<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmmoMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'ammo_id',
        'type',
        'boxes',
        'rounds',
        'unit_cost_box',
        'reference',
        'notes',
        'moved_at',
        'user_id',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
        'unit_cost_box' => 'decimal:2',
        'boxes' => 'integer',
    ];

    public function ammo()
    {
        return $this->belongsTo(Ammo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
