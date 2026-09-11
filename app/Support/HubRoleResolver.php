<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Translates an Access Hub person's `roles` array into the single role this
 * system stores on `users.role`.
 *
 * The hub vocabulary is fixed at four values (manager, division_head, vp,
 * user); the hub => local mapping lives in config/hub.php. This class owns the
 * one piece of logic the mapping table deliberately doesn't: when a person
 * carries more than one hub role, the highest-privilege mapped role wins.
 */
final class HubRoleResolver
{
    /**
     * Highest privilege first. Every value here must be a target in
     * config('hub.role_map').
     */
    private const PRIORITY = [
        'vp_gen_services',
        'division_head',
        'farm_manager',
    ];

    /**
     * @param  array<int, string>  $hubRoles  the person's `roles` array from the hub
     * @return string|null  the local role, or null if the hub grants this system
     *                      nothing for this person
     */
    public static function resolve(array $hubRoles): ?string
    {
        $map = (array) config('hub.role_map', []);

        $mapped = [];

        foreach ($hubRoles as $hubRole) {
            if (array_key_exists($hubRole, $map)) {
                $mapped[] = $map[$hubRole];

                continue;
            }

            // Unknown or intentionally unmapped (e.g. `user`). Skip the string,
            // never the person -- another of their roles may still be known.
            Log::warning("Hub role not mapped, skipping: {$hubRole}");
        }

        foreach (self::PRIORITY as $role) {
            if (in_array($role, $mapped, true)) {
                return $role;
            }
        }

        return null;
    }
}
