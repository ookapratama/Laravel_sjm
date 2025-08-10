<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'source',
        'amount',
        'notes',
        'payment_channel',
        'payment_reference',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
