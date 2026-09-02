<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * The clean state: reference/lookup data only -- everything a form needs to be
 * usable, and nothing else. Running `migrate:fresh --seed` must leave a usable
 * system with zero users and zero requests.
 *
 * This seeder never creates users and never calls TestSeeder or
 * DeploymentSeeder. Those are invoked explicitly, by name, on purpose.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // This system carries no lookup tables yet -- statuses, budget
        // categories and timelines are config-driven (config/project_timelines.php,
        // config/sidebar.php). Reference seeders go in Reference/ and get listed
        // here as the first lookup table lands.
        $this->call([
            //
        ]);
    }
}
