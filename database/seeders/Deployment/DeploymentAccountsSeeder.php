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
 * IMPORTANT: API login resolves a user by the local `users`.`id` PRIMARY KEY --
 * AuthController::attemptApiLogin() does `User::find($resolvedUserId)` on the id
 * the auth API returns for that email, not by `external_user_id` (that column
 * exists on the table but nothing reads it). So `id` below must be forced to
 * match the number the Auth API's /users/get-user-id endpoint returns for that
 * person. `id` is guarded (not mass-assignable), so it's set explicitly on the
 * model rather than passed through create()/updateOrCreate() -- a plain
 * mass-assignment call here would silently drop it and hand out an
 * auto-increment id instead, and that account would never be able to log in.
 */
class DeploymentAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['id' => 61, 'name' => 'IT Administrator', 'email' => 'i.guno@bfcgroup.org', 'role' => 'it_admin', 'farm' => null, 'department' => 'IT Administration'],
        ];

        if ($accounts === []) {
            $this->command?->warn('DeploymentAccountsSeeder has no accounts configured -- nothing seeded. Fill in $accounts before go-live.');

            return;
        }

        foreach ($accounts as $account) {
            // Matching on email keeps a second run from duplicating a real
            // person; the id is only ever set on the very first insert.
            $user = User::firstOrNew(['email' => $account['email']]);
            $isNew = ! $user->exists;

            if ($isNew) {
                $user->id = $account['id'];
                $user->password = Hash::make(Str::random(40));
            }

            $user->fill([
                'name' => $account['name'],
                'role' => $account['role'],
                'farm' => $account['farm'] ?? null,
                'department' => $account['department'] ?? null,
                'is_active' => true,
            ]);

            $user->save();
        }

        $this->command?->info('Seeded '.count($accounts).' deployment account(s).');
    }
}
