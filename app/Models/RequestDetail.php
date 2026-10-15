<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'item_id',
        'custom_item_name',
        'custom_category',
        'budget_type',
        'specification',
        'brand_type',
        'usage_purpose',
        'quantity',
        'stock_at_request',
        'shortage_quantity',
        'approved_quantity',
        'unit',
        'estimated_price',
        'product_url',
        'note',
    ];

    protected $casts = [
        'estimated_price' => 'decimal:2',
    ];

    /**
     * Detail ini milik satu pengajuan.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    /**
     * Barang master untuk pengajuan stok.
     *
     * Nilainya null untuk pengajuan custom.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * Memeriksa apakah detail merupakan barang stok.
     */
    public function isStockItem(): bool
    {
        return ! is_null($this->item_id);
    }

    /**
     * Memeriksa apakah detail merupakan barang custom.
     */
    public function isCustomItem(): bool
    {
        return is_null($this->item_id);
    }

    /**
     * Nama barang yang dapat digunakan untuk stok maupun custom.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->item_id && $this->item) {
            return $this->item->name;
        }

        return $this->custom_item_name ?: '-';
    }

    /**
     * Satuan barang.
     */
    public function getDisplayUnitAttribute(): string
    {
        if ($this->item_id && $this->item) {
            return $this->item->unit ?? '';
        }

        return $this->unit ?? '';
    }
}