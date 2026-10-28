<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengaturan tahap approval pengajuan custom.
 *
 * Satu baris = satu tahap tanda tangan pada Formulir Pengajuan Barang,
 * misalnya "Diketahui oleh" + jabatan "Spv. GA".
 */
class ApprovalStep extends Model
{
    use HasFactory;

    /**
     * Label tahap yang tersedia pada formulir.
     */
    public const LABELS = ['Diketahui oleh', 'Disetujui oleh'];

    protected $fillable = [
        'step_order',
        'label',
        'position_id',
        'is_active',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Jabatan seperti "Atasan Langsung" tidak cocok sebagai tahap tetap: atasan langsung
     * berbeda tiap pengajuan dan dipilih saat FPB dibuat.
     */
    public static function isSuperiorPosition(?string $positionName): bool
    {
        return str_contains(mb_strtolower((string) $positionName), 'atasan');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('step_order');
    }
}
