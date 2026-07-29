<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Gudang mempunyai banyak user.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Gudang mempunyai banyak data stok barang.
     */
    public function itemStocks(): HasMany
    {
        return $this->hasMany(ItemStock::class);
    }

    /**
     * Gudang mempunyai banyak transaksi stok masuk.
     */
    public function stockIns(): HasMany
    {
        return $this->hasMany(StockIn::class);
    }

    /**
     * Gudang mempunyai banyak pengajuan.
     */
    public function requests(): HasMany
    {
        return $this->hasMany(Request::class);
    }

    /**
     * Scope gudang aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope urutan nama gudang.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Label status.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->is_active
            ? 'Aktif'
            : 'Nonaktif';
    }

    /**
     * Label gudang lengkap.
     *
     * Contoh:
     * GD-PABRIK - Gudang Pabrik
     */
    public function getFullLabelAttribute(): string
    {
        return $this->code . ' - ' . $this->name;
    }
}