<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topup extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'for_cycle',
        'confirmed_at',
    ];

    protected $dates = ['confirmed_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
