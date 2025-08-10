<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public $timestamps = false; // karena kita pakai created_at manual
protected $casts = [
    'created_at' => 'datetime',
];
    protected $fillable = [
        'user_id',
        'message',
        'url',
        'created_at',
        'is_read', // Tambahkan field is_read
    ];
}
