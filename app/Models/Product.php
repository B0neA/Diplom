// app/Models/Product.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'description', 'price', 
        'rating', 'image', 'category', 'is_active'
    ];

    protected $casts = [
        'rating' => 'float',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Аксессор для получения полного URL изображения
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }
}