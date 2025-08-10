<?php

namespace App\Helpers;

use App\Models\User;

class TreeHelper
{
    public static function countDownlines(User $user, string $side): int
    {
        $count = 0;

        $child = User::where('upline_id', $user->id)
            ->where('position', $side)
            ->first();

        if ($child) {
            $count += 1;
            $count += self::countDownlines($child, 'left');
            $count += self::countDownlines($child, 'right');
        }

        return $count;
    }
}
