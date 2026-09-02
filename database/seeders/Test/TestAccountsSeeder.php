<?php

namespace Database\Seeders\Test;

use App\Models\User;
use App\Support\TestAccounts;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * The dummy accounts listed on the login page while testing mode is on, plus
 * the supporting cast the sample project data needs. Roster lives in
 * App\Support\TestAccounts so the seeder and the login panel can never drift.
 */
class TestAccountsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TestAccounts::all() as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'farm' => $account['farm'],
                    'department' => $account['department'],
                    'is_active' => true,
                    'password' => Hash::make(TestAccounts::PASSWORD),
                ]
            );
        }
    }
}
