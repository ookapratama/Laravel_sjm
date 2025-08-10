<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\BonusTransaction;
use App\Services\BonusManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BonusPairingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pairing_bonus_full_cycle_and_next_cycle_validation()
    {
        // Setup struktur upline > left & right
        $upline = User::factory()->create([
            'username' => 'upline',
            'pairing_level_count' => 0,
            'pairing_point' => 0,
            'bonus_voucher' => 10000000
        ]);

        $left = User::factory()->create([
            'username' => 'left',
            'pairing_point' => 1
        ]);

        $right = User::factory()->create([
            'username' => 'right',
            'pairing_point' => 1
        ]);

        // Hubungkan manual relasi anak ke upline (jika tidak pakai relasi model)
        $upline->setRelation('leftChild', $left);
        $upline->setRelation('rightChild', $right);

        // Simulasikan pairing level 1–10 (cycle 1)
for ($level = 1; $level <= 10; $level++) {
    $left->pairing_point = 1;
    $right->pairing_point = 1;
    $left->save();
    $right->save();

    BonusManager::process($upline, $left, $right, 0);
}


        $upline->refresh();

        // ✅ Validasi hasil cycle 1
        $this->assertEquals(10, $upline->pairing_level_count, 'Seharusnya pairing level_count = 10');
        $this->assertEquals(1, $upline->pairing_point, 'Seharusnya pairing_point = 1 (cycle 1 selesai)');
        $this->assertEquals(10, BonusTransaction::where('user_id', $upline->id)->count());

        // 🔁 Simulasi cycle 2 gagal (anak belum naik point)
        $left->pairing_point = 1;
        $right->pairing_point = 1;

        BonusManager::processPairing($upline, $left, $right, 1);
        $this->assertEquals(10, BonusTransaction::where('user_id', $upline->id)->count(), '❌ Bonus cycle 2 tidak boleh diberikan jika anak belum selesai');

        // ✅ Simulasi cycle 2 berhasil
        $left->pairing_point = 2;
        $right->pairing_point = 2;

        BonusManager::process($upline, $left, $right, 1);

        $upline->refresh();
        $this->assertEquals(11, BonusTransaction::where('user_id', $upline->id)->count(), '✅ Bonus pairing level 1 (Cycle 2) berhasil');
        $this->assertEquals(11, $upline->pairing_level_count);
        $this->assertEquals(1, $upline->pairing_point);
    }
}
