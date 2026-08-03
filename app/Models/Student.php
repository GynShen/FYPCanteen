<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
  use HasFactory;

  protected $fillable = [
    'school_id',
    'student_name',
    'class_name',
    'qr_code',
  ];

  public function school(): BelongsTo
  {
    return $this->belongsTo(School::class);
  }

  public function orderItems(): HasMany
  {
    return $this->hasMany(OrderItem::class);
  }

  public function child(): HasOne
  {
    return $this->hasOne(Children::class);
  }
}
