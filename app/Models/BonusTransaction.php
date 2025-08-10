<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'from_user_id',
        'type',
        'bagan',
        'status',
        'amount',
        'tax',
        'net_amount',
        'notes',
    ];
    public function user()
{
    return $this->belongsTo(\App\Models\User::class, 'user_id');
}

}
