<?php

namespace Database\Seeders;

use Database\Seeders\Test\TestAccountsSeeder;
use Database\Seeders\Test\TestProjectDataSeeder;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Everything fake: dummy accounts and sample records that exist only to look at
 * and click through. Local and staging only, layered on top of the clean state.
 */
class TestSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'TestSeeder refuses to run in production. It seeds dummy accounts with a '
                .'published password and fake project requests. Use DeploymentSeeder instead.'
            );
        }

        $this->call([
            TestAccountsSeeder::class,
            TestProjectDataSeeder::class,
        ]);
    }
}
