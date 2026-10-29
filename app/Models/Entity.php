<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entity extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'letterhead_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function accessUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'entity_user')
            ->withTimestamps();
    }

    public function itemStocks(): HasMany
    {
        return $this->hasMany(ItemStock::class);
    }

    public function stockIns(): HasMany
    {
        return $this->hasMany(StockIn::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(Request::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
