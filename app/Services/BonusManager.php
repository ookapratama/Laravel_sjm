<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserBagan;
use App\Models\UserPairingLog;
use App\Models\BonusTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BonusManager
{
    protected array $baganConfig = [
        1  => ['cost' => 750000,      'bonus' => 500000],
        2  => ['cost' => 1500000,     'bonus' => 1000000],
        3  => ['cost' => 3000000,     'bonus' => 2000000],
        4  => ['cost' => 6000000,     'bonus' => 4000000],
        5  => ['cost' => 12000000,    'bonus' => 8000000],
        6  => ['cost' => 24000000,    'bonus' => 16000000],
        7  => ['cost' => 48000000,    'bonus' => 32000000],
        8  => ['cost' => 96000000,    'bonus' => 64000000],
        9  => ['cost' => 192000000,   'bonus' => 128000000],
        10 => ['cost' => 384000000,   'bonus' => 256000000],
    ];

    /* =========================
     * ====== PUBLIC API =======
     * ========================= */

    public function process(User $user): void
    {
        // Ambil ulang bagans user terkini
        $user->load(['bagans' => function ($q) {
            $q->orderBy('bagan');
        }]);

        $activeBagans = $user->bagans;
        Log::info("📋 Bagan aktif untuk {$user->username}: " . json_encode($activeBagans->pluck('bagan')));

        foreach ($activeBagans as $userBagan) {
            $bagan = (int) $userBagan->bagan;

            if (! $userBagan->is_active) {
               Log::info("⏩ Lewati bagan $bagan karena belum aktif untuk {$user->username}");
            continue;
               
            }

            $level = (int) $userBagan->pairing_level_count + 1;
       Log::info("🔁 Memproses pairing untuk {$user->username} di Bagan {$bagan} mulai dari Level {$level}");

            // Iter sampai mentok (tidak eligible / anak belum siap)
            while ($this->canPairAtLevel($user, $bagan, $level)) {

                // Bagan >= 2 wajib anak kiri/kanan aktif di bagan & level sama
                if ($bagan > 1 && ! $this->isEligibleForBaganBonus($user, $bagan, $level)) {
                     Log::info("⛔ {$user->username} gagal pairing di Bagan {$bagan} Level {$level} — anak belum aktif di bagan/level yang sama");
                    break;
                }

                $this->givePairingBonus($user, $bagan, $level);

                // Update counter level di baris bagan ini saja (tanpa menimpa field lain)
                $userBagan->pairing_level_count = $level;
                $userBagan->save();

                // Selesai level 12 → aktivasi bagan berikut (dinamis)
                if ($level === 12 && $bagan < $this->maxBagan()) {
                    $this->activateNextBagan($user, $bagan + 1);
                }

                $level++;
            }
        }
    }

public function assignToUpline(User $user, ?User $upline = null, string $position = 'left', bool $autoSwitchIfBusy = false): void
{
    $position = strtolower($position) === 'right' ? 'right' : 'left';

    DB::transaction(function () use ($user, $upline, $position, $autoSwitchIfBusy) {

        // Idempoten: jika user sudah terpasang di slot yang sama, tidak perlu apa-apa
        if ($upline && $user->upline_id === $upline->id && $user->position === $position) {
            // tetap pastikan bagans ada:
            $uplineLevel = $this->getUserLevel($upline);
            $userLevel   = $uplineLevel + 1;
            $this->ensureAllBagans($upline, $upline->upline, $uplineLevel, $this->maxBagan(), [1 => true]);
            $this->ensureAllBagans($user,   $upline,        $userLevel,   $this->maxBagan(), [1 => true]);
            return;
        }

        if ($upline) {
            // --- VALIDASI SLOT: lock & cek apakah slot sudah terisi ---
            // Gunakan lock untuk cegah race-condition (dua proses sekaligus isi slot)
            $slotTerisi = User::where('upline_id', $upline->id)
                ->where('position', $position)
                ->lockForUpdate()
                ->exists();

            if ($slotTerisi) {
                if ($autoSwitchIfBusy) {
                    $alt = $position === 'left' ? 'right' : 'left';
                    $altTerisi = User::where('upline_id', $upline->id)
                        ->where('position', $alt)
                        ->lockForUpdate()
                        ->exists();
                    if ($altTerisi) {
                        throw new \InvalidArgumentException("Kedua slot pada upline #{$upline->id} sudah terisi.");
                    }
                    // pakai sisi alternatif
                    $position = $alt;
                } else {
                    throw new \InvalidArgumentException("Slot {$position} pada upline #{$upline->id} sudah terisi.");
                }
            }

            // Lindungi dari pemindahan user yang sudah terpasang di tempat lain tanpa sengaja
            if (!is_null($user->upline_id) && !is_null($user->position)) {
                // Jika mau izinkan “relokasi”, kamu bisa hapus guard ini.
                throw new \InvalidArgumentException("User #{$user->id} sudah terpasang (upline={$user->upline_id}, pos={$user->position}).");
            }

            // Pasang ke upline
            $user->upline_id = $upline->id;
            $user->position  = $position;
            $user->save();

            // Level relatif di jaringan
            $uplineLevel = $this->getUserLevel($upline);
            $userLevel   = $uplineLevel + 1;

            // Bootstrap bagans (upline & user)
            $this->ensureAllBagans($upline, $upline->upline, $uplineLevel, $this->maxBagan(), [1 => true]);
            $this->ensureAllBagans($user,   $upline,        $userLevel,   $this->maxBagan(), [1 => true]);

            // Proses pairing naik ke atas
            $current = $upline;
            while ($current) {
                $this->process($current);
                if (method_exists($current, 'refreshChildCounts')) {
                    $current->refreshChildCounts();
                }
                $current = $current->upline;
            }
        } else {
            // Root user (tanpa upline) → langsung 10 bagan
            // Guard: jangan overwrite jika sudah terpasang
            if (!is_null($user->upline_id) || !is_null($user->position)) {
                throw new \InvalidArgumentException("User #{$user->id} sudah terpasang, tidak bisa jadi root.");
            }
            $user->save();
            $this->ensureAllBagans($user, null, 1, $this->maxBagan(), [1 => true]);
        }
    });
}


    /* =========================
     * ====== CORE LOGIC =======
     * ========================= */

    protected function isEligibleForBaganBonus(User $user, int $bagan, int $level): bool
    {
        $left  = $this->getChildAtLevel($user, 'left',  $level);
        $right = $this->getChildAtLevel($user, 'right', $level);
        if (! $left || ! $right) return false;

        // Cek kedua anak aktif di bagan sama
        $leftActive  = UserBagan::where('user_id', $left->id)->where('bagan', $bagan)->where('is_active', true)->exists();
        $rightActive = UserBagan::where('user_id', $right->id)->where('bagan', $bagan)->where('is_active', true)->exists();

        return $leftActive && $rightActive;
    }

    protected function canPairAtLevel(User $user, int $bagan, int $level): bool
    {
        if ($this->hasReceivedPairing($user, $bagan, $level)) return false;

        $hasLeft  = $this->hasChildAtLevel($user, 'left',  $level);
        $hasRight = $this->hasChildAtLevel($user, 'right', $level);

        return $hasLeft && $hasRight;
    }

    protected function givePairingBonus(User $user, int $bagan, int $level): void
    {
        $bonus  = $this->baganBonus($bagan);
        $status = 'paid';
        $net    = $bonus - ($bonus * 0.05); // potongan default 5%
        $notes  = "Bonus pairing level {$level} (Bagan {$bagan})";

        // Level 10–12: tahan untuk upgrade ke bagan berikutnya jika belum aktif
        if (in_array($level, [10, 11, 12], true) && $bagan < $this->maxBagan()) {
            if (! $this->isBaganActive($user, $bagan + 1)) {
                $status = 'held';
                $net    = 0;
                $notes .= " - dialokasikan untuk upgrade ke Bagan " . ($bagan + 1);
                
            }
        }

        if ($status === 'held') {
            $this->allocateUpgradeBonus($user, $bagan, $bonus); // akumulasi ke next bagan
        }

        // Catat log pairing
        UserPairingLog::create([
            'user_id' => $user->id,
            'bagan'   => $bagan,
            'level'   => $level,
            'type'    => 'pairing',
            'notes'   => $notes,
        ]);

        // Catat transaksi bonus
        BonusTransaction::create([
            'user_id'    => $user->id,
            'bagan'      => $bagan,
            'level'      => $level,
            'amount'     => $bonus,
            'net_amount' => $net,
            'status'     => $status,
            'notes'      => $notes,
        ]);

        Log::info("✅ {$user->username} menerima bonus Rp" . number_format($bonus) . " (Bagan $bagan Level $level) [$status]");

        // Cek apakah alokasi membuat next bagan auto-aktif
        $this->checkAutoUpgradeBagan($user, $bagan);
    }

    protected function allocateUpgradeBonus(User $user, int $bagan, int $amount): bool
    {
        $nextBagan = $bagan + 1;
        $entry = UserBagan::where('user_id', $user->id)->where('bagan', $nextBagan)->first();

        if (! $entry || $entry->is_active) return false;

        $entry->allocated_from_bonus += $amount;
        $entry->save();

        if ($entry->allocated_from_bonus >= $entry->upgrade_cost && $entry->upgrade_cost > 0) {
            $entry->is_active = true;
            $entry->allocated_from_bonus = $entry->upgrade_cost; // clamp
            $entry->save();

           
            return true;
        }

        return false;
    }

    protected function checkAutoUpgradeBagan(User $user, int $bagan): void
    {
        $nextBagan = $bagan + 1;
        if ($nextBagan > $this->maxBagan()) return;

        $target = UserBagan::where('user_id', $user->id)->where('bagan', $nextBagan)->first();
        if (! $target || $target->is_active) return;

        if ($target->allocated_from_bonus >= $target->upgrade_cost && $target->upgrade_cost > 0) {
            $target->is_active = true;
            $target->allocated_from_bonus = $target->upgrade_cost;
            $target->upgrade_paid_manually = false;
            $target->save();

           Log::info("🚀 Upgrade otomatis ke Bagan {$nextBagan} untuk {$user->username}");
        }
    }

   protected function activateNextBagan(User $user, int $nextBagan): void
{
    if ($nextBagan > $this->maxBagan()) return;

    $existing = UserBagan::where('user_id', $user->id)
        ->where('bagan', $nextBagan)
        ->first();

    if ($existing) {
        if (! $existing->is_active) {
            $existing->is_active = true;
            $existing->save();
        }
        return;
          Log::info("🎉 {$user->username} aktif di Bagan $nextBagan");
    }

    // Jika belum ada barisnya, buat 1 baris untuk bagan berikutnya dan aktifkan
    $this->ensureBaganRow(
        $user,
        $nextBagan,
        $user->upline,
        $this->getUserLevel($user),
        true
    );
}




    protected function ensureAllBagans(User $user,?User $upline,int $level,?int $maxBagan = null,array $activeBagans = [1 => true] ): void 
    {
    $max = $maxBagan ?: $this->maxBagan();

    for ($b = 1; $b <= $max; $b++) {
        $this->ensureBaganRow(
            $user,
            $b,
            $upline,
            $level,
            (bool) ($activeBagans[$b] ?? false)
        );
    }
}


    /**
     * Create-or-update aman (update hanya jika perlu).
     * - Isi upline_id hanya jika masih null.
     * - Naikkan level jika lebih tinggi (tak pernah menurunkan).
     * - Set aktif jika diminta dan belum aktif.
     * - Isi upgrade_cost jika kosong/0.
     */
    protected function ensureBaganRow(User $user, int $bagan, ?User $upline, int $level, bool $activate): void
    {
        $upgradeCost = $this->baganCost($bagan);

        DB::transaction(function () use ($user, $bagan, $upline, $level, $activate, $upgradeCost) {

            $row = $user->bagans()->where('bagan', $bagan)->lockForUpdate()->first();

            if (! $row) {
                $user->bagans()->create([
                    'bagan'                 => $bagan,
                    'upline_id'             => $upline?->id, // boleh null (root sementara)
                    'level'                 => $level,
                    'is_active'             => $activate,
                    'pairing_level_count'   => 0,
                    'upgrade_cost'          => $upgradeCost,
                    'allocated_from_bonus'  => 0,
                    'upgrade_paid_manually' => false,
                ]);
                return;
            }

            $updates = [];

            if (is_null($row->upline_id) && $upline?->id) {
                $updates['upline_id'] = $upline->id;
            }
            if ($level > (int) $row->level) {
                $updates['level'] = $level;
            }
            if ($activate && ! $row->is_active) {
                $updates['is_active'] = true;
            }
            if ((empty($row->upgrade_cost) || (float)$row->upgrade_cost == 0.0) && $upgradeCost > 0) {
                $updates['upgrade_cost'] = $upgradeCost;
            }

            if (! empty($updates)) {
                $row->update($updates);
            }
        });
    }

    protected function isBaganActive(User $user, int $bagan): bool
    {
        return UserBagan::where('user_id', $user->id)
            ->where('bagan', $bagan)
            ->where('is_active', true)
            ->exists();
    }

    protected function hasReceivedPairing(User $user, int $bagan, int $level): bool
    {
        return UserPairingLog::where('user_id', $user->id)
            ->where('bagan', $bagan)
            ->where('level', $level)
            ->where('type', 'pairing')
            ->exists();
    }

    protected function hasChildAtLevel(User $user, string $side, int $level): bool
    {
        $child = $user->getChild($side);
        if (! $child) return false;

        return $this->hasDescendantAtLevel($child, $level - 1);
    }

    protected function hasDescendantAtLevel(User $user, int $depth): bool
    {
        if ($depth === 0) return true;

        // Query anak langsung, stop cepat bila ada yang memenuhi
        $children = User::where('upline_id', $user->id)->get();
        foreach ($children as $child) {
            if ($this->hasDescendantAtLevel($child, $depth - 1)) {
                return true;
            }
        }
        return false;
    }

    protected function getActiveChildAtLevel(User $user, string $side, int $level, int $bagan): ?User
    {
        $child = $this->getChildAtLevel($user, $side, $level);
        if (! $child) return null;

        $isActive = $child->bagans()->where('bagan', $bagan)->where('is_active', true)->exists();
        return $isActive ? $child : null;
    }

    protected function getChildAtLevel(User $user, string $position, int $level): ?User
    {
        $child = $user->getChild($position); // level 1
        for ($i = 1; $i < $level; $i++) {
            if (! $child) return null;
            $child = $child->getChild($position); // turun 1 level di posisi sama
        }
        return $child;
    }

    public function getUserLevel(User $user): int
    {
        $level = 0;
        while ($user->upline) {
            $level++;
            $user = $user->upline;
        }
        return $level;
    }

    /* =========================
     * ====== HELPERS ==========
     * ========================= */

    protected function maxBagan(): int
    {
        return max(array_keys($this->baganConfig));
    }

    protected function baganBonus(int $bagan): int
    {
        return (int) ($this->baganConfig[$bagan]['bonus'] ?? 0);
    }

    protected function baganCost(int $bagan): int
    {
        return (int) ($this->baganConfig[$bagan]['cost'] ?? 0);
    }
}
