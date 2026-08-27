<?php

namespace Database\Seeders;

use App\Models\ProjectReferenceLink;
use App\Models\ProjectRequest;
use App\Models\RequestAttachment;
use App\Models\RequestTransition;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Resets the demo environment to exactly what a live walkthrough needs:
 * every role account, ready to log into, and zero requests — so the very
 * first request of the demo is the one submitted live in front of the room.
 */
class PresentationReadySeeder extends Seeder
{
    public function run(): void
    {
        $this->wipeRequestData();
        $this->seedCoreAccounts();
        $this->seedEngineers();
        $this->pruneNonEssentialAccounts();
    }

    protected function wipeRequestData(): void
    {
        Schema::disableForeignKeyConstraints();

        RequestTransition::truncate();
        RequestAttachment::truncate();
        ProjectReferenceLink::truncate();
        ProjectRequest::truncate();
        DB::table('notifications')->truncate();

        Schema::enableForeignKeyConstraints();
    }

    protected function seedCoreAccounts(): void
    {
        $accounts = [
            ['name' => 'Jose Santos', 'email' => 'j.santos@brooksidegroup.org', 'role' => 'farm_manager', 'farm' => 'Farm A – Bamban, Tarlac', 'department' => null],
            ['name' => 'Div. Head Santos', 'email' => 'dh.santos@brooksidegroup.org', 'role' => 'division_head', 'farm' => null, 'department' => 'Office of the Division Head'],
            ['name' => 'Atty. T. Dizon', 'email' => 't.dizon@brooksidegroup.org', 'role' => 'vp_gen_services', 'farm' => null, 'department' => 'VP – General Services'],
            ['name' => 'Ancel Roque', 'email' => 'a.roque@brooksidegroup.org', 'role' => 'dh_gen_services', 'farm' => null, 'department' => 'DH – General Services'],
            ['name' => 'Engr. D. Baniaga', 'email' => 'd.baniaga@brooksidegroup.org', 'role' => 'ed_manager', 'farm' => null, 'department' => 'Office of the ED Manager'],
            ['name' => 'Jeff Montiano', 'email' => 'j.montiano@brooksidegroup.org', 'role' => 'it_admin', 'farm' => null, 'department' => 'IT Administration'],
            ['name' => 'Guest Viewer', 'email' => 'guest@brooksidegroup.org', 'role' => 'guest', 'farm' => null, 'department' => null],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'farm' => $account['farm'],
                    'department' => $account['department'],
                    'is_active' => true,
                    'password' => Hash::make('1234'),
                ]
            );
        }
    }

    protected function seedEngineers(): void
    {
        // Kept well under the 4-active-engineer cap (ITAdmin\UsersPage) so the
        // ED Manager has a real choice to make when assigning live.
        $engineers = [
            ['name' => 'Engr. L. Bautista', 'email' => 'l.bautista@brooksidegroup.org'],
            ['name' => 'Engr. R. Ramos', 'email' => 'r.ramos@brooksidegroup.org'],
        ];

        foreach ($engineers as $engineer) {
            User::updateOrCreate(
                ['email' => $engineer['email']],
                [
                    'name' => $engineer['name'],
                    'role' => 'engineer',
                    'farm' => null,
                    'department' => 'Engineering',
                    'is_active' => true,
                    'password' => Hash::make('1234'),
                ]
            );
        }
    }

    /**
     * Drops any account left over from earlier demo/test seeding that isn't
     * part of the essential roster above, so the Users page shows exactly
     * the accounts this walkthrough needs and nothing else.
     */
    protected function pruneNonEssentialAccounts(): void
    {
        $essentialEmails = [
            'j.santos@brooksidegroup.org',
            'dh.santos@brooksidegroup.org',
            't.dizon@brooksidegroup.org',
            'a.roque@brooksidegroup.org',
            'd.baniaga@brooksidegroup.org',
            'j.montiano@brooksidegroup.org',
            'guest@brooksidegroup.org',
            'l.bautista@brooksidegroup.org',
            'r.ramos@brooksidegroup.org',
        ];

        User::query()->whereNotIn('email', $essentialEmails)->delete();
    }
}
