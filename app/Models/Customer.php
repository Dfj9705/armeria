<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tax_name',
        'email',
        'phone',
        'nit',
        'cui',
        'address',
        'city',
        'state',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function getFelNameAttribute(): string
    {
        return $this->tax_name ?: $this->name;
    }

    public function getFelIdAttribute(): ?string
    {
        // Prioridad: NIT, si no CUI
        return $this->nit ?: $this->cui;
    }

    public function getFelIdTypeAttribute(): ?string
    {
        if ($this->nit)
            return 'nit';
        if ($this->cui)
            return 'cui';
        return null;
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

}
