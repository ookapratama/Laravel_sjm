<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'max_value',
        'is_active'
    ];

    protected $casts = [
        'max_value' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function packageProducts()
    {
        return $this->hasMany(ProductPackage::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'package_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function getTotalValueAttribute()
    {
        return $this->packageProducts->sum(function ($packageProduct) {
            return $packageProduct->product->price * $packageProduct->quantity;
        });
    }

    public function getTotalItemsAttribute()
    {
        return $this->packageProducts->sum('quantity');
    }

    public function getTotalProductsAttribute()
    {
        return $this->packageProducts->count();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
