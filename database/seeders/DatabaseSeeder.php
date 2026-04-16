<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Jose Santos', 'email' => 'j.santos@brooksidegroup.org', 'role' => 'farm_manager'],
            ['name' => 'Div. Head Santos', 'email' => 'dh.santos@brooksidegroup.org', 'role' => 'division_head'],
            ['name' => 'Atty. T. Dizon', 'email' => 't.dizon@brooksidegroup.org', 'role' => 'vp_gen_services'],
            ['name' => 'Ancel Roque', 'email' => 'a.roque@brooksidegroup.org', 'role' => 'dh_gen_services'],
            ['name' => 'Engr. D. Baniaga', 'email' => 'd.baniaga@brooksidegroup.org', 'role' => 'ed_manager'],
            ['name' => 'Jeff Montiano', 'email' => 'j.montiano@brooksidegroup.org', 'role' => 'it_admin'],
            ['name' => 'Guest Viewer', 'email' => 'guest@brooksidegroup.org', 'role' => 'guest'],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => Hash::make('1234'),
                ]
            );
        }
    }
}
