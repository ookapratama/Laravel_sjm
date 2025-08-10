<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivationPin extends Model
{
    protected $fillable=['code','status','bagan','price','purchased_by','pin_request_id','used_by','used_at'];
    protected $casts=['used_at'=>'datetime'];

    public function owner(){ return $this->belongsTo(User::class,'purchased_by'); }
    public function request(){ return $this->belongsTo(PinRequest::class,'pin_request_id'); }
}
