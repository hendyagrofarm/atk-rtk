<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model
{
    use HasFactory;

    /**
     * Alasan penolakan terstruktur (kunci => label) untuk laporan KPI.
     */
    public const REJECTION_REASONS = [
        'stok_tidak_cukup' => 'Stok tidak cukup / kosong',
        'tidak_sesuai_kebutuhan' => 'Tidak sesuai kebutuhan',
        'melebihi_kuota' => 'Melebihi kuota / batas pemakaian',
        'data_tidak_lengkap' => 'Data pengajuan tidak lengkap',
        'duplikat' => 'Pengajuan ganda',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = [
        'user_id',
        'entity_id',
        'warehouse_id',
        'approver_id',
        'request_number',
        'request_date',
        'request_type',
        'status',
        'note',
        'department',
        'pic_name',
        'pic_position',
        'superior_name',
        'superior_position',
        'superior_user_id',
        'pic_user_id',
        'pic_signature_path',
        'estimated_total',
        'approver_note',
        'rejection_reason',
        'approved_at',
        'bast_number',
        'bast_note',
        'handed_over_by',
        'handed_signature_path',
        'received_name',
        'received_user_id',
        'received_signature_path',
        'received_on_behalf',
        'received_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'received_on_behalf' => 'boolean',
        'estimated_total' => 'decimal:2',
    ];

    /**
     * User pembuat pengajuan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Entitas pengajuan.
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * Gudang sumber pengajuan.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * User yang memproses approval.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approver_id'
        );
    }

    /**
     * Detail barang pengajuan.
     */
    public function details(): HasMany
    {
        return $this->hasMany(
            RequestDetail::class,
            'request_id'
        );
    }

    /**
     * PIC (Diajukan oleh) bila berupa user di sistem.
     */
    public function picUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    /**
     * Atasan langsung yang dipilih dari user sistem (tahap approval digital).
     */
    public function superiorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'superior_user_id');
    }

    /**
     * Tahap approval bertingkat (hanya untuk pengajuan custom).
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(RequestApproval::class, 'request_id')
            ->orderBy('step_order');
    }

    /**
     * Pengajuan yang boleh diproses/dilihat user pada menu Approval (lokasi atau sebagai penyetuju).
     */
    public function scopeVisibleToApprover($query, User $user)
    {
        $allowedEntityIds = $user->allowedEntityIds();
        $allowedWarehouseIds = $user->allowedWarehouseIds();

        return $query->where(function ($scope) use ($user, $allowedEntityIds, $allowedWarehouseIds) {
            // Akun staff tidak mendapat akses berdasarkan lokasi, hanya sebagai penyetuju.
            if ($user->role !== 'staff') {
                $scope->where(function ($location) use ($user, $allowedEntityIds, $allowedWarehouseIds) {
                    $location
                        ->whereIn('entity_id', $allowedEntityIds)
                        ->whereIn('warehouse_id', $allowedWarehouseIds);

                    // Pengajuan custom bertingkat terpisah antar departemen: approver hanya melihatnya
                    // bila terlibat sebagai penyetuju, bukan karena satu lokasi.
                    if ($user->role === 'approver') {
                        $location->whereDoesntHave('approvals');
                    }
                });
            } else {
                // Kondisi yang tidak pernah terpenuhi agar orWhere di bawah tetap terstruktur.
                $scope->whereRaw('1 = 0');
            }

            // Tahap yang ditunjuk ke user ini (atasan langsung).
            $scope->orWhereHas(
                'approvals',
                fn ($approvals) => $approvals->where('assigned_user_id', $user->id)
            );

            // Tahap tetap berdasarkan jabatan user.
            if ($user->position_id) {
                $scope->orWhere(function ($chain) use ($user, $allowedEntityIds) {
                    $chain
                        ->whereIn('entity_id', $allowedEntityIds)
                        ->whereHas(
                            'approvals',
                            fn ($approvals) => $approvals
                                ->whereNull('assigned_user_id')
                                ->where('position_id', $user->position_id)
                        );
                });
            }
        });
    }

    /**
     * Tahap pembelanjaan sampai serah terima (pengajuan custom yang disetujui).
     */
    public function fulfillments(): HasMany
    {
        return $this->hasMany(RequestFulfillment::class, 'request_id')->orderBy('id');
    }

    public function handedOverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_over_by');
    }

    /**
     * Penerima barang bila punya akun: PIC pilihan, atau pembuat (staff) bila PIC tidak diisi manual.
     */
    public function receiverUser(): ?User
    {
        if ($this->pic_user_id) {
            return $this->picUser;
        }

        return filled($this->pic_name) ? null : $this->user;
    }

    public function receiverName(): ?string
    {
        return filled($this->pic_name) ? $this->pic_name : $this->user?->name;
    }

    /**
     * Pengajuan yang dibuat atau diterima (sebagai PIC) oleh user ini.
     */
    public function scopeMine($query, User $user)
    {
        return $query->where(function ($mine) use ($user) {
            $mine->where('user_id', $user->id)->orWhere('pic_user_id', $user->id);
        });
    }

    /**
     * Apakah pengajuan ini memakai alur approval bertingkat.
     */
    public function usesApprovalChain(): bool
    {
        return $this->isCustomRequest() && $this->approvals->isNotEmpty();
    }

    /**
     * Pengajuan dari stok.
     */
    public function isStockRequest(): bool
    {
        return $this->request_type === 'stock';
    }

    /**
     * Pengajuan barang custom.
     */
    public function isCustomRequest(): bool
    {
        return $this->request_type === 'custom';
    }

    /**
     * Label alasan penolakan, atau null jika tidak ada.
     */
    public function getRejectionReasonLabelAttribute(): ?string
    {
        return $this->rejection_reason
            ? (self::REJECTION_REASONS[$this->rejection_reason] ?? $this->rejection_reason)
            : null;
    }

    /**
     * Label jenis pengajuan.
     */
    public function getRequestTypeLabelAttribute(): string
    {
        return $this->request_type === 'custom'
            ? 'Barang Baru / Custom'
            : 'Barang Stok';
    }

    /**
     * Label lokasi pengajuan.
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
}