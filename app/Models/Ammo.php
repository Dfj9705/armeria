<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ammo extends Model
{
    use HasFactory;

    protected $fillable = [
        'ammo_id',
        'name',
        'brand_id',
        'caliber_id',
        'type',
        'price_per_box',
        'rounds_per_box',
        'boxes',
        'rounds',
        'unit_cost_box',
        'reference',
        'notes',
        'moved_at',
        'user_id',
        'images',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
        'unit_cost_box' => 'decimal:2',
        'boxes' => 'integer',
        'rounds' => 'integer',
        'price' => 'decimal:2',
        'images' => 'array',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function caliber()
    {
        return $this->belongsTo(Caliber::class);
    }

    public function movements()
    {
        return $this->hasMany(\App\Models\AmmoMovement::class);
    }

    public function getStockRoundsAttribute(): int
    {
        $rpb = (int) ($this->rounds_per_box ?? 0);

        $inBoxes = (int) $this->movements()->where('type', 'IN')->sum('boxes');
        $outBoxes = (int) $this->movements()->where('type', 'OUT')->sum('boxes');

        $inRounds = (int) $this->movements()->where('type', 'IN')->sum('rounds');
        $outRounds = (int) $this->movements()->where('type', 'OUT')->sum('rounds');

        return ($inBoxes * $rpb + $inRounds) - ($outBoxes * $rpb + $outRounds);
    }

    public function getStockBoxesAttribute(): int
    {
        $rpb = (int) ($this->rounds_per_box ?? 0);
        if ($rpb <= 0)
            return 0;

        return intdiv(max($this->stock_rounds, 0), $rpb);
    }

    public function getStockLooseRoundsAttribute(): int
    {
        $rpb = (int) ($this->rounds_per_box ?? 0);
        if ($rpb <= 0)
            return max($this->stock_rounds, 0);

        return max($this->stock_rounds, 0) % $rpb;
    }

}
