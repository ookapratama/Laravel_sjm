<?php

namespace App\Services;

use App\Models\User;

class UserTopUp
{
    public static function hasToppedUp(User $user, int $cycle): bool
    {
        // Logika topup di sini (dummy return true)
        return true;
    }
}
