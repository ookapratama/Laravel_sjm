<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'price',
        'pv',
        'stock',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'pv' => 'decimal:2',
        'is_active' => 'boolean'
    ];


    public function packageProducts()
    {
        return $this->hasMany(ProductPackage::class);
    }

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'package_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
