<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItemComponent extends Model
{
    protected $fillable = ['component_type', 'name', 'amount_g', 'sort_order'];

    protected function casts(): array
    {
        return ['amount_g' => 'decimal:2'];
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
