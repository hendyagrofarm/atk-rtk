<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pengeluaran barang langsung oleh admin pemegang stok.
 * Stok berkurang saat transaksi disimpan; transaksi tidak dapat diedit atau dihapus.
 */
class StockOut extends Model
{
    use HasFactory;

    protected $fillable = [
        'issue_number',
        'issue_date',
        'entity_id',
        'warehouse_id',
        'user_id',
        'recipient_name',
        'recipient_department',
        'note',
    ];

    protected $casts = [
        'issue_date' => 'date',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Admin yang mengeluarkan barang.
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOutItem::class);
    }

    /**
     * Batasi pada entitas dan gudang yang boleh diakses user.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query
            ->whereIn('entity_id', $user->allowedEntityIds())
            ->whereIn('warehouse_id', $user->allowedWarehouseIds());
    }

    /**
     * Terapkan filter laporan. Filter entitas atau gudang di luar hak akses diabaikan.
     *
     * @param  array<string, mixed>  $filters  start_date, end_date, entity_id, warehouse_id,
     *                                          category_id, issuer_id, search
     */
    public function scopeFiltered(Builder $query, array $filters, User $user): Builder
    {
        $isSuperAdmin = $user->isSuperAdmin();

        $entityId = filled($filters['entity_id'] ?? null) ? (int) $filters['entity_id'] : null;
        $warehouseId = filled($filters['warehouse_id'] ?? null) ? (int) $filters['warehouse_id'] : null;

        if ($entityId && ! $isSuperAdmin && ! $user->allowedEntityIds()->contains($entityId)) {
            $entityId = null;
        }

        if ($warehouseId && ! $isSuperAdmin && ! $user->allowedWarehouseIds()->contains($warehouseId)) {
            $warehouseId = null;
        }

        return $query
            ->when(filled($filters['start_date'] ?? null), fn ($q) => $q->whereDate('issue_date', '>=', $filters['start_date']))
            ->when(filled($filters['end_date'] ?? null), fn ($q) => $q->whereDate('issue_date', '<=', $filters['end_date']))
            ->when($entityId, fn ($q) => $q->where('entity_id', $entityId))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when(filled($filters['issuer_id'] ?? null), fn ($q) => $q->where('user_id', $filters['issuer_id']))
            ->when(
                filled($filters['category_id'] ?? null),
                fn ($q) => $q->whereHas(
                    'items.item',
                    fn ($iq) => $iq->where('category_id', $filters['category_id'])
                )
            )
            ->when(filled($filters['search'] ?? null), function ($q) use ($filters) {
                $search = trim((string) $filters['search']);

                $q->where(function ($sub) use ($search) {
                    $sub->where('issue_number', 'like', "%{$search}%")
                        ->orWhere('recipient_name', 'like', "%{$search}%")
                        ->orWhere('recipient_department', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhereHas('items.item', function ($iq) use ($search) {
                            $iq->where('code', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            });
    }
}
