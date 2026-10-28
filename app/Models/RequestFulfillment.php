<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu tahap pembelanjaan/serah terima pada pengajuan custom yang sudah disetujui.
 */
class RequestFulfillment extends Model
{
    public const PURCHASING = 'purchasing';
    public const ORDERED = 'ordered';
    public const ARRIVED = 'arrived';
    public const HANDED_OVER = 'handed_over';
    public const RECEIVED = 'received';

    /**
     * Urutan tahap (kunci => label).
     */
    public const STAGES = [
        self::PURCHASING => 'Diproses pembelian',
        self::ORDERED => 'Barang dipesan',
        self::ARRIVED => 'Barang tiba',
        self::HANDED_OVER => 'Diserahkan ke penerima',
        self::RECEIVED => 'Diterima pengguna',
    ];

    protected $fillable = [
        'request_id',
        'stage',
        'user_id',
        'vendor',
        'po_number',
        'eta_date',
        'note',
        'happened_at',
    ];

    protected $casts = [
        'eta_date' => 'date',
        'happened_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->stage] ?? $this->stage;
    }
}
