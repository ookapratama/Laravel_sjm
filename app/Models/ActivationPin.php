<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivationPin extends Model
{
    protected $fillable = ['code', 'status', 'bagan', 'price', 'purchased_by', 'pin_request_id', 'used_by', 'used_at', 'transferred_date', 'transferred_to', 'transferred_notes', 'product_package_id', 'created_at'];
    protected $casts = ['used_at' => 'datetime'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }
    public function request()
    {
        return $this->belongsTo(PinRequest::class, 'pin_request_id');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function posSession()
    {
        return $this->hasOne(PosSessions::class, 'activation_pin_id');
    }

    public function posItems()
    {
        return $this->hasManyThrough(PosItems::class, PosSessions::class, 'activation_pin_id', 'session_id');
    }

    // Helper methods
    public function getMaxProductsAttribute()
    {
        return match($this->bagan) {
            1 => 3,    // Basic
            2 => 5,    // Premium  
            3 => null, // VIP - unlimited
            default => 3
        };
    }

    public function getBaganNameAttribute()
    {
        return match($this->bagan) {
            1 => 'Basic',
            2 => 'Premium',
            3 => 'VIP',
            default => 'Unknown'
        };
    }

    public function getBaganBadgeAttribute()
    {
        $class = match($this->bagan) {
            1 => 'secondary',
            2 => 'primary',
            3 => 'success',
            default => 'secondary'
        };
        
        return '<span class="badge badge-' . $class . '">' . $this->bagan_name . '</span>';
    }

    // Scopes
    public function scopeNeedsPosHandling($query)
    {
        return $query->where('status', 'used')
                    ->whereNull('product_package_id')
                    ->whereDoesntHave('posSession');
    }

    public function scopePosInProgress($query)
    {
        return $query->where('status', 'used')
                    ->whereHas('posSession', function($q) {
                        $q->where('session_status', 'pending');
                    });
    }
}
