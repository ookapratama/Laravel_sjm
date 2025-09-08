<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosSessions extends Model
{
    use HasFactory;

    protected $fillable = [
        'activation_pin_id',
        'member_id',
        'admin_id',
        'session_status',
        'total_budget',
        'used_budget',
        'remaining_budget',
        'total_pv',
        'max_products',
        'products_count',
        'notes',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'total_budget' => 'decimal:2',
        'used_budget' => 'decimal:2',
        'remaining_budget' => 'decimal:2',
        'total_pv' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function activationPin()
    {
        return $this->belongsTo(ActivationPin::class, 'activation_pin_id');
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function items()
    {
        return $this->hasMany(PosItems::class, 'session_id');
    }

    // Helper methods
    public function getTotalPvAttribute()
    {
        return $this->items->sum('total_pv');
    }

    public function canAddProduct($productPrice = 0, $quantity = 1)
    {
        $budgetOk = ($this->remaining_budget >= ($productPrice * $quantity));
        $countOk = $this->max_products ? 
                   ($this->products_count + $quantity <= $this->max_products) : true;
        
        return $budgetOk && $countOk;
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->session_status) {
            'pending' => '<span class="badge badge-warning">Pending</span>',
            'active' => '<span class="badge badge-info">Active</span>',
            'completed' => '<span class="badge badge-success">Completed</span>',
            'cancelled' => '<span class="badge badge-danger">Cancelled</span>',
            default => '<span class="badge badge-secondary">Unknown</span>'
        };
    }

    public function getDurationAttribute()
    {
        if ($this->completed_at && $this->started_at) {
            return $this->started_at->diffInMinutes($this->completed_at) . ' menit';
        }
        
        if ($this->started_at) {
            return $this->started_at->diffInMinutes(now()) . ' menit';
        }
        
        return '-';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('session_status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('session_status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
