<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_id',
        'item_id',
        'current_stock',
        'minimum_stock',
    ];

    /**
     * Stok ini milik satu entitas.
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * Stok ini milik satu barang.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Mengecek apakah stok barang di entitas ini menipis.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->minimum_stock;
    }
}