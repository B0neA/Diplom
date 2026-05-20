<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'rating', 'slug', 'image', 'description',
        'address', 'phone', 'email', 'opening_time', 'closing_time', 'is_active'
    ];

    protected $casts = [
        'rating' => 'float',
        'is_active' => 'boolean',
    ];

    // Автоматическая генерация slug
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($restaurant) {
            $restaurant->slug = Str::slug($restaurant->title);
        });
    }

    // Связи
    public function categories()
    {
        return $this->hasMany(Category::class)->orderBy('sort_order');
    }

    public function dishes()
    {
        return $this->hasMany(Dish::class);
    }

    // Аксессор для URL изображения
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : '/images/placeholder.jpg';
    }

    // Скоуп для активных ресторанов
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}