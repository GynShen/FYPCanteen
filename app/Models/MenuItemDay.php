<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItemDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'day_of_week',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
