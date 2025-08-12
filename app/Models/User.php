<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    use Notifiable;
    use HasFactory;
    use HasRoles;
    public function mitraProfile()
    {
        return $this->hasOne(MitraProfile::class);
    }

    public function mitra()
    {
        return $this->hasOne(MitraProfile::class, 'user_id', 'id');
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class, 'user_id', 'id');
    }
    
    public function preRegistration()
    {
        return $this->hasOne(PreRegistration::class);
    }
    public function hasDownline(): bool
    {
        return $this->children()->count() > 0;
    }
    public function hasAnyChild(): bool
    {
        return $this->getLeftChild()?->exists() || $this->getRightChild()?->exists();
    }

    public function left()
    {
        return $this->hasMany(User::class, 'upline_id')->where('position', 'left');
    }

    // Mendapatkan satu anak dengan posisi kanan
    public function right()
    {
        return $this->hasMany(User::class, 'upline_id')->where('position', 'right');
    }
    public function upline()
    {
        return $this->belongsTo(User::class, 'upline_id');
    }

    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    public function children()
    {
        return $this->hasMany(User::class, 'upline_id');
    }

    public function pairingLogs()
    {
        return $this->hasMany(UserPairingLog::class);
    }
    public function getLeftChild()
    {
        return $this->children()->where('position', 'left')->first();
    }

    public function getRightChild()
    {
        return $this->children()->where('position', 'right')->first();
    }
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'sponsor_id',
        'upline_id',
        'position',
        'level',
        'is_active',
        'joined_at',
        'tax_id',
        'address',
        'bank_account',
        'kiri_count',
        'kanan_count',
        'pairing_count',
        'pairing_child_count',
        'ro_purchase_count'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
        'bank_account' => 'array',
    ];
    public function hasAnyChildAtLevel(int $targetLevel): bool
    {
        return $this->pairing_level_count >= $targetLevel;
    }


    public function refreshChildCounts()
    {
        $this->kiri_count = $this->children()->where('position', 'left')->count();
        $this->kanan_count = $this->children()->where('position', 'right')->count();
        $this->save();
    }

    public function incrementLegCount($position)
    {
        if ($position === 'left') {
            $this->increment('kiri_count');
        } elseif ($position === 'right') {
            $this->increment('kanan_count');
        }
    }
    protected static function booted()
    {
        static::creating(function ($user) {
            $prefix = strtoupper(substr(preg_replace('/\s+/', '', $user->name), 0, 4)); // ambil 3 huruf pertama dari nama (tanpa spasi)
            $prefix = str_pad($prefix, 4, 'X'); // kalau kurang dari 4 huruf, tambahkan X

            do {
                $random = strtoupper(Str::random(5)); // sisa 5 karakter random
                $code = $prefix . $random;
            } while (User::where('referral_code', $code)->exists());

            $user->referral_code = $code;
        });
    }



    public function hasCompletedCycle(int $cycle): bool
    {
        $count = $this->pairingLogs()
            ->where('cycle', $cycle)
            ->count();

        Log::info("🔍 {$this->username} has {$count} pairing log(s) for cycle {$cycle}");

        return $count >= 3;
    }



    public function leftChild()
    {
        return $this->hasOne(User::class, 'upline_id')->where('position', 'left');
    }

    public function rightChild()
    {
        return $this->hasOne(User::class, 'upline_id')->where('position', 'right');
    }


    public function hasPairableChildrenAtLevel(int $level): bool
    {
        return $this->hasChildAtLevel('left', $level) && $this->hasChildAtLevel('right', $level);
    }

    public function hasChildAtLevel(string $position, int $targetLevel, int $currentLevel = 1): bool
    {
        $childRelation = $position === 'left' ? $this->left() : $this->right();
        $children = $childRelation->get();

        foreach ($children as $child) {
            if ($currentLevel === $targetLevel) {
                return true;
            }

            if ($child->hasChildAtLevel($position, $targetLevel, $currentLevel + 1)) {
                return true;
            }
        }

        return false;
    }

    public function descendants()
    {
        $descendants = collect();

        $left = $this->getLeftChild();
        if ($left) {
            $descendants->push($left);
            $descendants = $descendants->merge($left->descendants());
        }

        $right = $this->getRightChild();
        if ($right) {
            $descendants->push($right);
            $descendants = $descendants->merge($right->descendants());
        }

        return $descendants;
    }
    public function bagans()
    {
        return $this->hasMany(UserBagan::class)->orderBy('bagan');
    }
    // App\Models\User.php

    public function getDirectChild(int $bagan, string $position)
    {
        return $this->children()
            ->where('position', $position)
            ->whereHas('userBagans', fn($q) => $q->where('bagan', $bagan))
            ->first();
    }
    public function userBagans()
    {
        return $this->hasMany(UserBagan::class)->orderBy('bagan');
    }
    public function getChild(string $position): ?User
    {
        return User::where('upline_id', $this->id)
            ->where('position', $position)
            ->first();
    }
}
