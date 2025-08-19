<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserBagan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class NetworkPlacer
{
    /**
     * Tempel user ke tree pada bagan tertentu.
     * - Validasi slot kosong (left/right) pada parent & bagan
     * - Update user_bagans (upline_id, position)
     */
    public function place(User $user, int $parentId, string $position, int $bagan = 1): void
    {
        $position = strtolower($position);
        if (!in_array($position, ['left', 'right'], true)) {
            throw new InvalidArgumentException('Posisi harus left/right');
        }

        DB::transaction(function () use ($user, $parentId, $position, $bagan) {
            // ambil record user_bagans si user pada bagan tsb
            /** @var UserBagan $ub */
            $ub = UserBagan::where('user_id', $user->id)
                ->where('bagan', $bagan)
                ->lockForUpdate()
                ->first();

            if (!$ub) {
                // jika belum ada, buat minimal
                $ub = UserBagan::create([
                    'user_id'               => $user->id,
                    'bagan'                 => $bagan,
                    'is_active'             => true,
                    'upline_id'             => null,
                    'position'              => null,
                    'level'                 => 1,
                    'pairing_level_count'   => 0,
                    'upgrade_cost'          => 1500000,
                    'allocated_from_bonus'  => 0,
                    'upgrade_paid_manually' => 0,
                ]);
            }

            // Cek apakah slot parent di bagan ini sudah terisi
            $slotSudahTerisi = UserBagan::where('bagan', $bagan)
                ->where('upline_id', $parentId)
                ->where('position', $position)
                ->exists();

            if ($slotSudahTerisi) {
                // kalau penuh, lempar exception agar caller bisa handle (pending placement, dsb)
                throw new InvalidArgumentException("Slot {$position} pada parent #{$parentId} (bagan {$bagan}) sudah terisi.");
            }

            // set hubungan
            $ub->upline_id = $parentId;
            $ub->position  = $position;
            $ub->save();
        });
    }
}
