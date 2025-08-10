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
        0 => ['cost' => 750000, 'bonus' => 500000],
        1 => ['cost' => 1500000, 'bonus' => 1000000],
        2 => ['cost' => 3000000, 'bonus' => 2000000],
        3 => ['cost' => 6000000, 'bonus' => 4000000],
        4 => ['cost' => 12000000, 'bonus' => 8000000],
        5 => ['cost' => 24000000, 'bonus' => 16000000],
    ];

public function process(User $user): void
{
    // Ambil ulang user_bagans dari DB agar valid dan selalu update
    $activeBagans = UserBagan::where('user_id', $user->id)->get();
    Log::info("📋 Bagan aktif untuk {$user->username}: " . json_encode($activeBagans->pluck('bagan')));

    foreach ($activeBagans as $userBagan) {
        $bagan = $userBagan->bagan;

        // Pastikan hanya lanjut jika benar-benar aktif di DB
        if (!$this->isBaganActive($user, $bagan)) {
            Log::info("⏩ Lewati bagan $bagan karena belum aktif untuk {$user->username}");
            continue;
        }

        $level = $userBagan->pairing_level_count + 1;

        Log::info("🔁 Memproses pairing untuk {$user->username} di Bagan {$bagan} mulai dari Level {$level}");

        while ($this->canPairAtLevel($user, $bagan, $level)) {
             // ⛔ Validasi khusus untuk bagan ≥ 2
            if ($bagan > 1 && !$this->isEligibleForBaganBonus($user, $bagan, $level)) {
                Log::info("⛔ {$user->username} gagal pairing di Bagan {$bagan} Level {$level} — anak belum aktif di bagan/level yang sama");
                break;
            }
            $this->givePairingBonus($user, $bagan, $level);

            $userBagan->pairing_level_count = $level;
            $userBagan->save();

            if ($level === 12 && $bagan < 5) {
                $this->activateNextBagan($user, $bagan + 1);
            }

            $level++;
        }
    }
}

protected function isEligibleForBaganBonus(User $user, int $bagan, int $level): bool
{
    $left = $this->getChildAtLevel($user, 'left', $level);
    $right = $this->getChildAtLevel($user, 'right', $level);

    if (!$left || !$right) return false;

    $leftBagan = $left->bagans->firstWhere('bagan', $bagan);
    $rightBagan = $right->bagans->firstWhere('bagan', $bagan);

    if (!$leftBagan || !$rightBagan) return false;

    return $leftBagan->is_active && $rightBagan->is_active;
}

public function assignToUpline(User $user, ?User $upline = null, string $position = 'left'): void
{
    if ($upline) {
        // User dipasang ke upline
        $user->upline_id = $upline->id;
        $user->position = $position;
        $user->save();

        $level = $this->getUserLevel($upline) + 1;

        // Pastikan upline punya Bagan 1 aktif
        $this->ensureUserBaganExists($upline, 1);

        // Bagan 1 aktif untuk user
        $user->bagans()->create([
            'bagan' => 1,
            'upline_id' => $upline->id,
            'level' => $level,
            'is_active' => true,
            'pairing_level_count' => 0,
            'upgrade_cost' => $this->baganConfig[1]['cost'] ?? 0,
            'allocated_from_bonus' => 0,
            'upgrade_paid_manually' => false,
        ]);
        
        // Bagan 2–5 tidak aktif
       foreach ([2, 3, 4, 5] as $bagan) {

    $nextBagan = $bagan;
    $upgradeCost = $this->baganConfig[$nextBagan]['cost'] ?? 0;
    log::info("cek bagan {$nextBagan} cots {$upgradeCost}");
    
    $user->bagans()->create([
        'bagan' => $bagan,
        'upline_id' => $upline->id ?? null,
        'level' => $level,
        'is_active' => false,
        'pairing_level_count' => 0,
        'upgrade_cost' => $upgradeCost,
        'allocated_from_bonus' => 0,
        'upgrade_paid_manually' => false,
    ]);
}

        Log::info("👥 {$user->username} dipasang ke {$upline->username} di posisi {$position}");

        // Proses pairing ke atas
        $current = $upline;
        while ($current) {
            Log::info("🔁 Memproses pairing untuk {$current->username}");
            $this->process($current);
            $current->refreshChildCounts();
            $current = $current->upline;
        }

    } else {
        // User pertama (tanpa upline)
        $user->save(); // pastikan tersimpan

        Log::info("✅ Bagan 1–5 dibuat untuk {$user->username} tanpa upline (user pertama)");
    }
}


    protected function ensureUserBaganExists(User $user, int $baganUtama = 1, ?User $upline = null): void
    {
        if ($user->bagans()->where('bagan', $baganUtama)->exists()) return;

        $level = $upline ? $this->getUserLevel($upline) + 1 : 1;
        $uplineId = $upline?->id;

        $user->bagans()->create([
            'bagan' => $baganUtama,
            'upline_id' => $uplineId,
            'level' => $level,
            'is_active' => true,
            'pairing_level_count' => 0,
        ]);

              foreach ([2, 3, 4, 5] as $bagan) {

    $nextBagan = $bagan;
    $upgradeCost = $this->baganConfig[$nextBagan]['cost'] ?? 0;
    $user->bagans()->create([
        'bagan' => $bagan,
        'upline_id' => $upline->id ?? null,
        'level' => $level,
        'is_active' => false,
        'pairing_level_count' => 0,
        'upgrade_cost' => $upgradeCost,
        'allocated_from_bonus' => 0,
        'upgrade_paid_manually' => false,
    ]);
}
        Log::info("✅ Bagan 1–5 dibuat untuk {$user->username}");
    }
protected function canPairAtLevel(User $user, int $bagan, int $level): bool
{
    if ($this->hasReceivedPairing($user, $bagan, $level)) return false;

    $hasLeft = $this->hasChildAtLevel($user, 'left', $level);
    $hasRight = $this->hasChildAtLevel($user, 'right', $level);

    return $hasLeft && $hasRight;
}
protected function checkAutoUpgradeBagan(User $user, int $bagan): void
{
    $nextBagan = $bagan + 1;

    if (!isset($this->baganConfig[$nextBagan])) return;

    $targetBagan = $user->bagans()->where('bagan', $nextBagan)->first();

    if (!$targetBagan || $targetBagan->is_active) return;

    if ($targetBagan->allocated_from_bonus >= $targetBagan->upgrade_cost) {
        $targetBagan->is_active = true;
        $targetBagan->allocated_from_bonus = $targetBagan->upgrade_cost;
        $targetBagan->upgrade_paid_manually = false;
        $targetBagan->save();

        Log::info("🚀 Upgrade otomatis ke Bagan {$nextBagan} untuk {$user->username}");
    }
}

protected function allocateUpgradeBonus(User $user, int $bagan, int $amount): bool
{
    $nextBagan = $bagan + 1;
    $nextBaganEntry = $user->bagans()->where('bagan', $nextBagan)->first();

    if (!$nextBaganEntry || $nextBaganEntry->is_active) {
        return false; // Sudah aktif atau tidak ada
    }

    $nextBaganEntry->allocated_from_bonus += $amount;
    $nextBaganEntry->save();

    if ($nextBaganEntry->allocated_from_bonus >= $nextBaganEntry->upgrade_cost) {
        $nextBaganEntry->is_active = true;
        $nextBaganEntry->save();

        Log::info("🚀 {$user->username} upgrade otomatis ke Bagan {$nextBagan}");

        return true; // Upgrade berhasil
    }

    return false; // Masih dalam proses akumulasi
}




protected function givePairingBonus(User $user, int $bagan, int $level): void
{
    $bonus = $this->baganConfig[$bagan]['bonus'];
    $status = 'paid';
    $net = $bonus - ($bonus * 0.05); // default potongan 5%
    $notes = "Bonus pairing level $level (Bagan $bagan)";

    // Jika belum aktif di bagan selanjutnya dan level 10–12 → tahan untuk upgrade
    if (in_array($level, [10,11,12]) && isset($this->baganConfig[$bagan + 1])) {
    if (!$this->isBaganActive($user, $bagan + 1)) {
        $status = 'held';
        $net = 0;
        $notes .= " - dialokasikan untuk upgrade ke Bagan " . ($bagan + 1);
            Log::info("🚫 Bonus level $level untuk {$user->username} DITAHAN karena belum aktif di bagan " . ($bagan + 1));
    }
}
if ($status === 'held') {
    $this->allocateUpgradeBonus($user, $bagan, $bonus);
}

    UserPairingLog::create([
        'user_id' => $user->id,
        'bagan' => $bagan,
        'level' => $level,
        'type' => 'pairing',
        'notes' => $notes,
    ]);

    BonusTransaction::create([
        'user_id' => $user->id,
        'bagan' => $bagan,
        'level' => $level,
        'amount' => $bonus,
        'net_amount' => $net,
        'status' => $status,
        'notes' => $notes,
    ]);

    Log::info("✅ {$user->username} menerima bonus Rp" . number_format($bonus) . " (Bagan $bagan Level $level) [$status]");
    
    $this->checkAutoUpgradeBagan($user, $bagan);
}


protected function activateNextBagan(User $user, int $nextBagan): void
{
    $existing = UserBagan::where('user_id', $user->id)
        ->where('bagan', $nextBagan)
        ->first();

    if ($existing) {
        if (!$existing->is_active) {
            $existing->is_active = true;
            $existing->save();
            Log::info("🔓 {$user->username} aktif kembali di Bagan $nextBagan");
        }
        return;
    }

    UserBagan::create([
        'user_id' => $user->id,
        'bagan' => $nextBagan,
        'pairing_level_count' => 0,
        'is_active' => true,
    ]);

    Log::info("🎉 {$user->username} aktif di Bagan $nextBagan");
}

protected function hasChildAtLevel(User $user, string $side, int $level): bool
{
    $child = $user->getChild($side);
    if (!$child) return false;

    return $this->hasDescendantAtLevel($child, $level - 1);
}
protected function hasDescendantAtLevel(User $user, int $depth): bool
{
    if ($depth === 0) return true;

    $children = User::where('upline_id', $user->id)->get();

    foreach ($children as $child) {
        if ($this->hasDescendantAtLevel($child, $depth - 1)) {
            return true;
        }
    }

    return false;
}
/**
 * Dapatkan anak kiri/kanan yang aktif di bagan tertentu dan level tertentu
 */
protected function getActiveChildAtLevel(User $user, string $side, int $level, int $bagan): ?User
{
    $child = $this->getChildAtLevel($user, $side, $level); // method existing

    if (!$child) return null;

    $isActive = $child->bagans()->where('bagan', $bagan)->where('is_active', true)->exists();

    return $isActive ? $child : null;
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

 protected function getChildAtLevel(User $user, string $position, int $level): ?User
{
    $child = $user->getChild($position); // level 1

    for ($i = 1; $i < $level; $i++) {
        if (!$child) return null;
        $child = $child->getChild($position); // ke level berikutnya
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


}
