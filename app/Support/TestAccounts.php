<?php

namespace App\Support;

/**
 * The single source of truth for dummy accounts: seeded by
 * Database\Seeders\Test\TestAccountsSeeder and listed on the login page while
 * testing mode is on.
 */
class TestAccounts
{
    /**
     * Deliberately obvious and non-secret -- these credentials are printed on
     * the login page, so there is nothing to keep.
     */
    public const PASSWORD = '1234';

    /**
     * One account per role, so a tester can switch perspective without hunting
     * for credentials. These are the accounts the login panel renders.
     */
    public static function panel(): array
    {
        return [
            ['label' => 'Farm Manager', 'name' => 'Jose Santos', 'email' => 'j.santos@brooksidegroup.org', 'role' => 'farm_manager', 'farm' => 'Farm A – Bamban, Tarlac', 'department' => null],
            ['label' => 'Division Head', 'name' => 'Div. Head Santos', 'email' => 'dh.santos@brooksidegroup.org', 'role' => 'division_head', 'farm' => null, 'department' => 'Office of the Division Head'],
            ['label' => 'VP Gen Services', 'name' => 'Atty. T. Dizon', 'email' => 't.dizon@brooksidegroup.org', 'role' => 'vp_gen_services', 'farm' => null, 'department' => 'VP – General Services'],
            ['label' => 'ED Manager', 'name' => 'Engr. D. Baniaga', 'email' => 'd.baniaga@brooksidegroup.org', 'role' => 'ed_manager', 'farm' => null, 'department' => 'Office of the ED Manager'],
            ['label' => 'DH Gen Services', 'name' => 'Ancel Roque', 'email' => 'a.roque@brooksidegroup.org', 'role' => 'dh_gen_services', 'farm' => null, 'department' => 'DH – General Services'],
            ['label' => 'Engineer', 'name' => 'Engr. L. Bautista', 'email' => 'l.bautista@brooksidegroup.org', 'role' => 'engineer', 'farm' => null, 'department' => 'Engineering'],
            ['label' => 'IT Admin', 'name' => 'Jeff Montiano', 'email' => 'j.montiano@brooksidegroup.org', 'role' => 'it_admin', 'farm' => null, 'department' => 'IT Administration'],
            ['label' => 'Guest', 'name' => 'Guest Viewer', 'email' => 'guest@brooksidegroup.org', 'role' => 'guest', 'farm' => null, 'department' => null],
        ];
    }

    /**
     * Extra accounts the sample project data needs (several farms to spread
     * requests across, a second engineer so assignment is a real choice). Not
     * shown on the login panel -- one card per role is the point of that list.
     */
    public static function supporting(): array
    {
        return [
            ['name' => 'Maria Cruz', 'email' => 'm.cruz@brooksidegroup.org', 'role' => 'farm_manager', 'farm' => 'Farm B – Capas, Tarlac', 'department' => null],
            ['name' => 'Pedro Reyes', 'email' => 'p.reyes@brooksidegroup.org', 'role' => 'farm_manager', 'farm' => 'Farm C – Concepcion, Tarlac', 'department' => null],
            ['name' => 'Ramon Torres', 'email' => 'r.torres@brooksidegroup.org', 'role' => 'farm_manager', 'farm' => 'Farm D – Angeles, Pampanga', 'department' => null],
            ['name' => 'Engr. R. Ramos', 'email' => 'r.ramos@brooksidegroup.org', 'role' => 'engineer', 'farm' => null, 'department' => 'Engineering'],
        ];
    }

    public static function all(): array
    {
        return array_merge(static::panel(), static::supporting());
    }
}
