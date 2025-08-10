<?php
namespace Database\Seeders;
use App\Models\Topup;
use App\Models\User;
use Illuminate\Database\Seeder;

class TopupSeeder extends Seeder
{
    public function run()
    {
        $users = User::take(5)->get();

        foreach ($users as $user) {
            Topup::create([
                'user_id' => $user->id,
                'amount' => 1500000,
                'type' => 'manual',
                'for_cycle' => 0,
                'confirmed_at' => now(),
            ]);
        }
    }
}

