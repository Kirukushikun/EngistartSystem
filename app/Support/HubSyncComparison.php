<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Compares the Access Hub's grant list against the local `users` table and
 * splits the difference into the three groups the guide requires:
 *
 *   new         - hub has them, this system does not (and the hub grants
 *                 something for them, and they're active)
 *   changed     - a hub-owned local row whose access would differ, or who went
 *                 inactive at the hub
 *   local_only  - local rows the hub doesn't mention (shown for reassurance,
 *                 never written)
 *
 * Rows with source = 'manual' are never candidates for change or revoke, even
 * when the hub lists them -- they simply don't appear in any group.
 */
final class HubSyncComparison
{
    /**
     * @param  array<int, array<string, mixed>>  $hubPeople  the hub's `people` array
     * @return array{new: array<int, array>, changed: array<int, array>, local_only: array<int, array>}
     */
    public static function build(array $hubPeople): array
    {
        /** @var Collection<int, User> $local */
        $local = User::all()->keyBy('id');

        $seenIds = [];
        $new = [];
        $changed = [];

        foreach ($hubPeople as $person) {
            $id = (int) ($person['user_id'] ?? 0);

            if ($id === 0) {
                continue;
            }

            $seenIds[] = $id;

            $desired = self::desiredFrom($person);
            $existing = $local->get($id);

            if ($existing === null) {
                // Brand new. Only worth showing if the hub actually grants a
                // role and the person is active -- otherwise there's nothing to
                // create.
                if ($desired['role'] !== null && $desired['active']) {
                    $new[] = $desired;
                }

                continue;
            }

            // A row this system owns by hand is off-limits to sync.
            if ($existing->source !== 'hub') {
                continue;
            }

            $current = self::snapshot($existing);

            // The hub no longer grants this person anything (gone inactive, or
            // every role they have is unmapped) -- that's a revoke, not a role
            // change. Only surface it if they're still enabled here.
            if (! $desired['active'] || $desired['role'] === null) {
                if ($existing->is_active) {
                    $changed[] = $desired + ['current' => $current, 'revoke' => true];
                }

                continue;
            }

            if (self::differs($current, $desired)) {
                $changed[] = $desired + ['current' => $current, 'revoke' => false];
            }
        }

        $localOnly = $local
            ->reject(fn (User $user) => in_array($user->id, $seenIds, true))
            ->map(fn (User $user) => self::snapshot($user))
            ->values()
            ->all();

        return [
            'new' => $new,
            'changed' => $changed,
            'local_only' => $localOnly,
        ];
    }

    /**
     * @param  array<string, mixed>  $person
     * @return array<string, mixed>
     */
    private static function desiredFrom(array $person): array
    {
        return [
            'id' => (int) $person['user_id'],
            'name' => (string) ($person['name'] ?? ''),
            'email' => (string) ($person['email'] ?? ''),
            'role' => HubRoleResolver::resolve((array) ($person['roles'] ?? [])),
            'hub_roles' => array_values((array) ($person['roles'] ?? [])),
            'farm' => self::nullableString($person['farm'] ?? null),
            'department' => self::nullableString($person['department'] ?? null),
            'position' => self::nullableString($person['position'] ?? null),
            'active' => (bool) ($person['active'] ?? true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function snapshot(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'farm' => $user->farm,
            'department' => $user->department,
            'position' => $user->position,
            'active' => (bool) $user->is_active,
            'source' => $user->source,
        ];
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $desired
     */
    private static function differs(array $current, array $desired): bool
    {
        foreach (['name', 'email', 'role', 'farm', 'department', 'position'] as $key) {
            if (($current[$key] ?? null) !== ($desired[$key] ?? null)) {
                return true;
            }
        }

        return $current['active'] !== $desired['active'];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
