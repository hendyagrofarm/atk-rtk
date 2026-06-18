<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'item_id',
        'quantity',
        'approved_quantity',
        'note',
    ];

    /**
     * Detail ini milik satu permintaan.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    /**
     * Detail ini berisi satu barang.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}