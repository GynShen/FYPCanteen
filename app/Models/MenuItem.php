<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'image_drive_id',
        'image_url',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

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

    public function menuCategories(): BelongsToMany
    {
        return $this->belongsToMany(MenuCategory::class, 'menu_category_menu_items');
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_menu_items')->withTimestamps();
    }

    public function days(): HasMany
    {
        return $this->hasMany(MenuItemDay::class);
    }
}
