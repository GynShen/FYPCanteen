<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItemSize extends Model
{
    use HasFactory;

    protected $fillable = ['menu_item_id', 'size', 'price_adjustment'];

    protected function casts(): array
    {
        return ['price_adjustment' => 'decimal:2'];
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
