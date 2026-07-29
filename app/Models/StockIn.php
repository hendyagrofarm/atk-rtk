<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_id',
        'warehouse_id',
        'item_id',
        'user_id',
        'quantity',
        'date',
        'supplier',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
    ];

    /**
     * Entitas penerima stok.
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * Gudang penerima stok.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Barang yang masuk.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * User yang mencatat transaksi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Label tujuan stok.
     *
     * Contoh:
     * ANR - Gudang Head Office
     */
    public function getDestinationLabelAttribute(): string
    {
        $entityCode = $this->entity?->code ?? '-';
        $warehouseName = $this->warehouse?->name ?? '-';

        return $entityCode . ' - ' . $warehouseName;
    }
}