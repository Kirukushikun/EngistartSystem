<?php

namespace Database\Seeders\Deployment;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The real accounts the live system starts with -- at minimum an IT Admin, so
 * someone can grant access to everyone else from the console on day one.
 *
 * Fill $accounts in before go-live. `external_user_id` must match the ID the
 * auth API returns for that person, since API login resolves the local user by
 * it; leave it null while the system runs in local auth mode.
 *
 * Passwords here are placeholders: under ENGISTART_AUTH_MODE=api credentials are
 * validated externally and the local hash is never checked.
 */
class DeploymentAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // ['name' => '', 'email' => '', 'role' => 'it_admin', 'farm' => null, 'department' => null, 'external_user_id' => null],
        ];

        if ($accounts === []) {
            $this->command?->warn('DeploymentAccountsSeeder has no accounts configured -- nothing seeded. Fill in $accounts before go-live.');

            return;
        }

        foreach ($accounts as $account) {
            // updateOrCreate on the email keeps a second run from duplicating a
            // real person.
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'farm' => $account['farm'] ?? null,
                    'department' => $account['department'] ?? null,
                    'external_user_id' => $account['external_user_id'] ?? null,
                    'is_active' => true,
                    'password' => Hash::make(Str::random(40)),
                ]
            );
        }

        $this->command?->info('Seeded '.count($accounts).' deployment account(s).');
    }
}
