<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id', 'amount', 'tax', 'status', 'admin_notes','transfer_reference','type'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
