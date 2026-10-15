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
        'estimated_total',
        'approver_note',
        'rejection_reason',
        'approved_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
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
     * Tahap approval bertingkat (hanya untuk pengajuan custom).
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(RequestApproval::class, 'request_id')
            ->orderBy('step_order');
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