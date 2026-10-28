<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu tahap approval pada satu pengajuan custom.
 *
 * Label dan nama jabatan disalin saat pengajuan dibuat, jadi perubahan
 * pengaturan alur tidak mengubah riwayat pengajuan yang sudah berjalan.
 */
class RequestApproval extends Model
{
    use HasFactory;

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const SKIPPED = 'skipped';

    protected $fillable = [
        'request_id',
        'step_order',
        'label',
        'position_id',
        'position_name',
        'assigned_user_id',
        'status',
        'user_id',
        'acted_at',
        'note',
        'signature_path',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'acted_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * User yang ditunjuk khusus untuk tahap ini (atasan langsung), jika ada.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }
}
