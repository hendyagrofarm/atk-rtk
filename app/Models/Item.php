<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'code',
        'name',
        'type',
        'unit',
        'minimum_stock',
        'current_stock',
        'storage_location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'minimum_stock' => 'integer',
        'current_stock' => 'integer',
    ];

    /**
     * Satu barang dimiliki oleh satu kategori.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Satu barang bisa punya banyak stok berdasarkan entitas/perusahaan.
     * Contoh:
     * - Pulpen Biru stok ANR
     * - Pulpen Biru stok MAA
     */
    public function itemStocks(): HasMany
    {
        return $this->hasMany(ItemStock::class);
    }

    /**
     * Satu barang bisa punya banyak riwayat barang masuk.
     */
    public function stockIns(): HasMany
    {
        return $this->hasMany(StockIn::class);
    }

    /**
     * Satu barang bisa muncul di banyak detail permintaan.
     */
    public function requestDetails(): HasMany
    {
        return $this->hasMany(RequestDetail::class);
    }

    /**
     * Mengecek apakah stok utama barang sudah menipis.
     *
     * Catatan:
     * Ini masih memakai kolom current_stock dan minimum_stock di tabel items.
     * Setelah stok per entitas ANR/MAA aktif penuh, pengecekan stok menipis
     * yang lebih tepat adalah dari tabel item_stocks.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->minimum_stock;
    }
}