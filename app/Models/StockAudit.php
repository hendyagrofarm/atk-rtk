<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_number',
        'audit_date',
        'entity_id',
        'warehouse_id',
        'item_id',
        'user_id',
        'system_stock',
        'physical_stock',
        'difference',
        'reason',
        'note',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'system_stock' => 'integer',
        'physical_stock' => 'integer',
        'difference' => 'integer',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDifferenceLabelAttribute(): string
    {
        if ($this->difference > 0) {
            return '+' . number_format($this->difference);
        }

        return number_format($this->difference);
    }

    public function getStatusLabelAttribute(): string
    {
        return match (true) {
            $this->difference > 0 => 'Stok Bertambah',
            $this->difference < 0 => 'Stok Berkurang',
            default => 'Sesuai',
        };
    }
}
