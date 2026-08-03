<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
  use HasFactory;

  protected $fillable = [
    'menu_category_id',
    'name',
    'description',
    'day_of_week',
    'price',
    'image_url',
    'is_available',
  ];

  public function orderItems(): HasMany
  {
    return $this->hasMany(OrderItem::class);
  }

  public function cartItems(): HasMany
  {
    return $this->hasMany(CartItem::class);
  }

  public function schoolMenuItems(): HasMany
  {
    return $this->hasMany(SchoolMenuItem::class);
  }

  public function menuCategory(): BelongsTo
  {
    return $this->belongsTo(MenuCategory::class);
  }
}
