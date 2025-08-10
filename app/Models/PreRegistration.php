<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'sponsor_id',
        'payment_method',
        'payment_proof',
        'status',
        'user_id', // optional, if you want to link to a user after approval
    ];
    public function user()
{
    return $this->belongsTo(User::class);
}
}
