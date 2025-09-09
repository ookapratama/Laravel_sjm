<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOutgoing extends Model
{
    use HasFactory;
    protected $table = 'product_outgoing';

    protected $fillable = [
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'unit_pv',
        'total_pv',
        'notes',
        'reference_code',
        'created_by'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'unit_pv' => 'decimal:2',
        'total_pv' => 'decimal:2'
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Auto-calculate saat creating
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($outgoing) {
            if ($outgoing->product) {
                $outgoing->unit_price = $outgoing->product->price;
                $outgoing->unit_pv = $outgoing->product->pv;
                $outgoing->total_price = $outgoing->unit_price * $outgoing->quantity;
                $outgoing->total_pv = $outgoing->unit_pv * $outgoing->quantity;
            }
        });

        // Update stock produk setelah record dibuat
        static::created(function ($outgoing) {
            $outgoing->product->decrement('stock', $outgoing->quantity);
        });
    }

    // Scope untuk filter
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
}
