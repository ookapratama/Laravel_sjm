<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosItems extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'unit_pv',
        'total_pv',
        'added_by'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'unit_pv' => 'decimal:2',
        'total_pv' => 'decimal:2'
    ];

     public function session()
    {
        return $this->belongsTo(PosSessions::class, 'session_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    // Auto-calculate totals when creating
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($item) {
            if ($item->product) {
                $item->unit_price = $item->product->price;
                $item->unit_pv = $item->product->pv;
                $item->total_price = $item->unit_price * $item->quantity;
                $item->total_pv = $item->unit_pv * $item->quantity;
            }
        });
    }
}
