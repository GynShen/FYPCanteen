<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'collected_quantity',
        'status',
        'is_late_collection',
        'collected_at',
        'collected_by',
    ];

    protected function casts(): array
    {
        return [
            'collected_at' => 'datetime',
            'is_late_collection' => 'boolean',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
