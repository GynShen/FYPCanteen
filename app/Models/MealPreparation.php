<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealPreparation extends Model
{
    protected $fillable = ['school_id', 'menu_item_id', 'preparation_date', 'completed_at', 'completed_by'];

    protected function casts(): array
    {
        return ['preparation_date' => 'date', 'completed_at' => 'datetime'];
    }
}
