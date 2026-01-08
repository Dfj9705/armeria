<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessoryCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_active'];

    public function accessories(): HasMany
    {
        return $this->hasMany(Accessory::class, 'category_id');
    }
}
