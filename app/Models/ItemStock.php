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
        'warehouse_id',
        'item_id',
        'current_stock',
        'minimum_stock',
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'minimum_stock' => 'integer',
    ];

    /**
     * Stok ini milik satu entitas.
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * Stok ini berada di satu gudang.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Stok ini milik satu barang.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Mengecek apakah stok menipis.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->minimum_stock;
    }

    /**
     * Label lokasi stok.
     *
     * Contoh:
     * ANR - Gudang Pabrik
     */
    public function getLocationLabelAttribute(): string
    {
        $entityCode = $this->entity?->code ?? '-';
        $warehouseName = $this->warehouse?->name ?? '-';

        return $entityCode . ' - ' . $warehouseName;
    }

    /**
     * Scope filter entitas.
     */
    public function scopeForEntity($query, $entityId)
    {
        return $query->where('entity_id', $entityId);
    }

    /**
     * Scope filter gudang.
     */
    public function scopeForWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope filter entitas dan gudang.
     */
    public function scopeForLocation(
        $query,
        $entityId,
        $warehouseId
    ) {
        return $query
            ->where('entity_id', $entityId)
            ->where('warehouse_id', $warehouseId);
    }
}