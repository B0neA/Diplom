<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Dish extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'price', 'image',
        'restaurant_id', 'category_id', 'is_available', 'is_popular',
        'sort_order', 'modifiers'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'is_popular' => 'boolean',
        'modifiers' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($dish) {
            $dish->slug = Str::slug($dish->name);
        });
    }

    // Связи
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Аксессор для URL изображения
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : '/images/placeholder.jpg';
    }

    // Скоупы
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeByRestaurant($query, $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }
}