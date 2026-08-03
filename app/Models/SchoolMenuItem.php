<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolMenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'menu_item_id',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
