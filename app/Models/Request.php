<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entity_id',
        'approver_id',
        'request_number',
        'request_date',
        'status',
        'note',
        'approver_note',
        'approved_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * User yang membuat permintaan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Entitas/perusahaan pengajuan: ANR atau MAA.
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * User admin/approver yang memproses permintaan.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Satu permintaan memiliki banyak detail barang.
     */
    public function details(): HasMany
    {
        return $this->hasMany(RequestDetail::class, 'request_id');
    }
}