<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandModel extends Model
{
    use HasFactory;

    protected $table = 'brand_models';

    protected $fillable = ['brand_id', 'name', 'is_active'];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
