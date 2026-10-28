<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOutItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_out_id',
        'item_id',
        'quantity',
        'stock_before',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'stock_before' => 'integer',
    ];

    public function stockOut(): BelongsTo
    {
        return $this->belongsTo(StockOut::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
