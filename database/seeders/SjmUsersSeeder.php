<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SjmUsersSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Admin
        User::updateOrCreate(
            ['username' => "admin"],
            [
                'name'              => "SJM Admin",
                'email'             => "admin@sjm.local",
                'password'          => Hash::make('admin'),
                'referral_code'     => sprintf('ADM%03d', 1),
                'joined_at'         => $now,
                'is_active'         => true,
                'role'              => 'admin',     // biarkan default kalau tak perlu
                'no_telp'           => '',
                'sponsor_id'        => null,
                'upline_id'         => null,
            ]
        );

        // Super Admin
        User::updateOrCreate(
            ['username' => "superadmin"],
            [
                'name'              => "Super Admin",
                'email'             => "superadmin@sjm.local",
                'password'          => Hash::make('superadmin'),
                'referral_code'     => sprintf('SPRADM%03d', 1),
                'joined_at'         => $now,
                'is_active'         => true,
                'role'              => 'super-admin',     // biarkan default kalau tak perlu
                'no_telp'           => '',
                'sponsor_id'        => null,
                'upline_id'         => null,
            ]
        );

        // Finance
        User::updateOrCreate(
            ['username' => "finance"],
            [
                'name'              => "Finance",
                'email'             => "finance@sjm.local",
                'password'          => Hash::make('finance'),
                'referral_code'     => sprintf('FNC%03d', 1),
                'joined_at'         => $now,
                'is_active'         => true,
                'role'              => 'finance',     // biarkan default kalau tak perlu
                'no_telp'           => '',
                'sponsor_id'        => null,
                'upline_id'         => null,
            ]
        );

        // 15 user untuk SJM
        for ($i = 1; $i <= 15; $i++) {
            User::updateOrCreate(
                ['username' => "sjm{$i}"],
                [
                    'name'              => "SJM User {$i}",
                    'email'             => "sjm{$i}@sjm.local",
                    'password'          => Hash::make('password'),
                    'referral_code'     => sprintf('SJM%03d', $i),
                    'joined_at'         => $now,
                    'is_active'         => true,
                    'role'              => 'member',     // biarkan default kalau tak perlu
                    'no_telp'           => '',
                    'sponsor_id'        => null,
                    'upline_id'         => null,
                ]
            );
        }

        // 16 user untuk SJM_PH
        for ($i = 1; $i <= 16; $i++) {
            User::updateOrCreate(
                ['username' => "sjmph{$i}"],
                [
                    'name'              => "SJM_PH User {$i}",
                    'email'             => "sjmph{$i}@sjm.local",
                    'password'          => Hash::make('password'),
                    'referral_code'     => sprintf('PH%03d', $i),
                    'joined_at'         => $now,
                    'is_active'         => true,
                    'role'              => 'member',
                    'no_telp'           => '',
                    'sponsor_id'        => null,
                    'upline_id'         => null,
                ]
            );
        }
    }
}
