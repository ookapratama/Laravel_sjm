<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

    class IncomeDetail extends Model
{
    protected $fillable = ['date', 'pendaftaran_member', 'manajemen','produk','pairing_bonus','reward_poin','ro_bonus','withdraw'];
    protected $casts = ['date' => 'date'];
}

