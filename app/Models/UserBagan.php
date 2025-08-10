<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBagan extends Model
{
    protected $fillable = [
    'user_id',
    'upline_id',
    'bagan',
    'level',
    'is_active',
    'pairing_level_count',
    'held_bonus',
    'upgrade_cost',
    'allocated_from_bonus',
    'upgrade_paid_manually',
    'upgrade_paid_at',
    'status',
    'bukti_transfer',
    'approved_by_admin',
    'approved_by_finance',
    'activated_at',
];

    protected $casts = [
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
