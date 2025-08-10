<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PinRequest extends Model
{
    protected $fillable = ['requester_id','qty','unit_price','total_price','status',
        'payment_method','payment_reference','payment_proof','finance_notes','finance_id','finance_at',
        'admin_id','generated_count','admin_notes','generated_at'];

    public function requester(){ return $this->belongsTo(User::class,'requester_id'); }
    public function pins(){ return $this->hasMany(ActivationPin::class,'pin_request_id'); }

    public function scopeOpen($q){ return $q->whereIn('status',['requested','finance_approved']); }
}

