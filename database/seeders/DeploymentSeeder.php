<?php

namespace Database\Seeders;

use Database\Seeders\Deployment\DeploymentAccountsSeeder;
use Illuminate\Database\Seeder;

/**
 * The real initial data a live system needs on day one -- actual people, actual
 * records. Run once, by hand, at go-live:
 *
 *     php artisan db:seed --class=DeploymentSeeder
 *
 * Never wired into `migrate:fresh --seed`: a fresh migrate wipes the database,
 * and a seeder that inserts real data has no business being one keystroke away
 * from that. Every seeder it calls must be idempotent -- this gets run on a live
 * system, possibly nervously, possibly twice.
 */
class DeploymentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DeploymentAccountsSeeder::class,
        ]);
    }
}
