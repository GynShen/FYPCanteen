<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMealEvent extends Model
{
    protected $fillable = ['student_id', 'order_item_id', 'meal_collection_id', 'event_type', 'reported_by', 'reported_at'];

    protected function casts(): array
    {
        return ['reported_at' => 'datetime'];
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function mealCollection(): BelongsTo { return $this->belongsTo(MealCollection::class); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
}
