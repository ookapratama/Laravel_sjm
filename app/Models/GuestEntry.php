<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class GuestEntry extends Model
{
    protected $fillable = [
        'invitation_id','referrer_user_id','referral_code','name','phone','email',
        'notes','attend_status','check_in_at','ip_address','user_agent'
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
    ];
        public function scopeVisibleFor(Builder $q, User $user): Builder
        {
            // Role yang boleh lihat semua
            if (in_array($user->role, ['admin','super-admin','finance'], true)) {
                return $q;
            }

            // Default: member → filter by referral_code
            $ref = $user->referral_code ?: $user->username;
            if ($ref) {
                return $q->where('referral_code', $ref);
            }

            // Kalau user tidak punya referral code sama sekali,
            // jangan tampilkan apa-apa (aman)
            return $q->whereRaw('1=0');
        }
    public function invitation() { return $this->belongsTo(Invitation::class); }
    public function referrer() { return $this->belongsTo(User::class, 'referrer_user_id'); }
}
