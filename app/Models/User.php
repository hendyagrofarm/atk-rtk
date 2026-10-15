<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Kolom yang boleh diisi melalui create() atau update().
     *
     * entity_id dan warehouse_id tetap digunakan sebagai lokasi default.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'entity_id',
        'warehouse_id',
        'position_id',
        'is_active',
    ];

    /**
     * Kolom yang disembunyikan ketika user dijadikan array atau JSON.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting atribut user.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Lokasi default
    |--------------------------------------------------------------------------
    */

    /**
     * Entitas default user.
     */
    /**
     * Jabatan user, dipakai pada alur approval pengajuan custom.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * Gudang default user.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Hak akses banyak entitas dan gudang
    |--------------------------------------------------------------------------
    */

    /**
     * Daftar entitas yang dapat diakses user.
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(
            Entity::class,
            'entity_user',
            'user_id',
            'entity_id'
        )->withTimestamps();
    }

    /**
     * Daftar gudang yang dapat diakses user.
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(
            Warehouse::class,
            'user_warehouse',
            'user_id',
            'warehouse_id'
        )->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Pemeriksaan role dan status
    |--------------------------------------------------------------------------
    */

    /**
     * Mengecek apakah user memiliki role tertentu.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Mengecek apakah user memiliki salah satu role.
     *
     * Contoh:
     * auth()->user()->hasAnyRole(['super_admin', 'admin'])
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Mengecek apakah user adalah Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Mengecek apakah akun user aktif.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Mengecek apakah user mempunyai entitas default.
     */
    public function hasEntity(): bool
    {
        return ! is_null($this->entity_id);
    }

    /**
     * Mengecek apakah user mempunyai gudang default.
     */
    public function hasWarehouse(): bool
    {
        return ! is_null($this->warehouse_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper hak akses
    |--------------------------------------------------------------------------
    */

    /**
     * Mendapatkan ID semua entitas yang boleh diakses user.
     *
     * Super Admin mendapatkan seluruh entitas aktif.
     *
     * @return Collection<int, int>
     */
    public function allowedEntityIds(): Collection
    {
        if ($this->isSuperAdmin()) {
            return Entity::query()
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id);
        }

        $entityIds = $this->entities()
            ->pluck('entities.id')
            ->map(fn ($id) => (int) $id);

        /*
         * Fallback untuk data lama yang belum masuk tabel pivot.
         */
        if ($entityIds->isEmpty() && $this->entity_id) {
            $entityIds->push((int) $this->entity_id);
        }

        return $entityIds->unique()->values();
    }

    /**
     * Mendapatkan ID semua gudang yang boleh diakses user.
     *
     * Super Admin mendapatkan seluruh gudang aktif.
     *
     * @return Collection<int, int>
     */
    public function allowedWarehouseIds(): Collection
    {
        if ($this->isSuperAdmin()) {
            return Warehouse::query()
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id);
        }

        $warehouseIds = $this->warehouses()
            ->pluck('warehouses.id')
            ->map(fn ($id) => (int) $id);

        /*
         * Fallback untuk data lama yang belum masuk tabel pivot.
         */
        if ($warehouseIds->isEmpty() && $this->warehouse_id) {
            $warehouseIds->push((int) $this->warehouse_id);
        }

        return $warehouseIds->unique()->values();
    }

    /**
     * Mengecek apakah user boleh mengakses entitas tertentu.
     */
    public function canAccessEntity(int|string|null $entityId): bool
    {
        if (is_null($entityId)) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->allowedEntityIds()
            ->contains((int) $entityId);
    }

    /**
     * Mengecek apakah user boleh mengakses gudang tertentu.
     */
    public function canAccessWarehouse(int|string|null $warehouseId): bool
    {
        if (is_null($warehouseId)) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->allowedWarehouseIds()
            ->contains((int) $warehouseId);
    }

    /**
     * Mengecek apakah user boleh mengakses kombinasi entitas dan gudang.
     */
    public function canAccessLocation(
        int|string|null $entityId,
        int|string|null $warehouseId
    ): bool {
        return $this->canAccessEntity($entityId)
            && $this->canAccessWarehouse($warehouseId);
    }

    /**
     * Mengecek apakah user memiliki lebih dari satu akses entitas.
     */
    public function hasMultipleEntities(): bool
    {
        return $this->allowedEntityIds()->count() > 1;
    }

    /**
     * Mengecek apakah user memiliki lebih dari satu akses gudang.
     */
    public function hasMultipleWarehouses(): bool
    {
        return $this->allowedWarehouseIds()->count() > 1;
    }

    /*
    |--------------------------------------------------------------------------
    | Label lokasi
    |--------------------------------------------------------------------------
    */

    /**
     * Label lokasi default user.
     *
     * Contoh:
     * ANR - Gudang Pabrik
     */
    public function getWorkLocationLabelAttribute(): string
    {
        if ($this->isSuperAdmin()) {
            return 'Semua Entitas - Semua Gudang';
        }

        $entityName = $this->entity?->code;
        $warehouseName = $this->warehouse?->name;

        if ($entityName && $warehouseName) {
            return $entityName . ' - ' . $warehouseName;
        }

        if ($entityName) {
            return $entityName;
        }

        if ($warehouseName) {
            return $warehouseName;
        }

        return 'Belum ditentukan';
    }

    /**
     * Ringkasan jumlah hak akses lokasi.
     */
    public function getAccessSummaryAttribute(): string
    {
        if ($this->isSuperAdmin()) {
            return 'Semua entitas dan semua gudang';
        }

        $entityCount = $this->allowedEntityIds()->count();
        $warehouseCount = $this->allowedWarehouseIds()->count();

        return $entityCount . ' entitas, '
            . $warehouseCount . ' gudang';
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi transaksi
    |--------------------------------------------------------------------------
    */

    /**
     * User dapat mencatat banyak stok masuk.
     */
    public function stockIns(): HasMany
    {
        return $this->hasMany(StockIn::class);
    }

    /**
     * User dapat membuat banyak pengajuan barang.
     */
    public function requests(): HasMany
    {
        return $this->hasMany(Request::class);
    }


    /**
     * User dapat mencatat banyak audit stok.
     */
    public function stockAudits(): HasMany
    {
        return $this->hasMany(StockAudit::class);
    }

    /**
     * User approver dapat memproses banyak pengajuan.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(
            Request::class,
            'approver_id'
        );
    }
}
