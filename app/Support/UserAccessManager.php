<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The single write path into `users` for access changes.
 *
 * Both IT Admin's User Management panel (source 'manual') and Access Hub sync
 * (source 'hub') go through here, so id handling, the engineer cap, and the
 * password placeholder stay consistent -- there is no second path.
 */
final class UserAccessManager
{
    public const MAX_ACTIVE_ENGINEERS = 4;

    /**
     * Create or update a user's access.
     *
     * $attrs requires `name`, `email`, `role`. `farm`, `department`, `position`
     * and `active` are written only when their key is present, so a role-only
     * edit from the panel can leave them out and keep the stored values, while
     * a hub sync passes `active` to enforce the hub's state (including
     * reinstating a previously-revoked hub row).
     *
     * @param  array<string, mixed>  $attrs
     * @param  string  $source  'manual' or 'hub'
     * @return array{user: User, notice: string|null}
     */
    public static function assign(int $id, array $attrs, string $source): array
    {
        $user = User::find($id);
        $isNew = $user === null;

        if ($isNew) {
            $user = new User();
            // `id` is guarded. Mass assignment silently drops it and the login
            // flow (User::find on the hub/directory id) would never match.
            $user->id = $id;
            $user->password = Hash::make(Str::random(40));
            $user->is_active = true;
        }

        $user->name = (string) $attrs['name'];
        $user->email = (string) $attrs['email'];
        $user->source = $source;

        $wasActiveEngineer = ! $isNew
            && $user->getOriginal('role') === 'engineer'
            && (bool) $user->getOriginal('is_active');

        $user->role = (string) $attrs['role'];

        foreach (['farm', 'department', 'position'] as $key) {
            if (array_key_exists($key, $attrs)) {
                $user->{$key} = self::blankToNull($attrs[$key]);
            }
        }

        if (array_key_exists('active', $attrs)) {
            $user->is_active = (bool) $attrs['active'];
        }

        $notice = null;

        if ($user->role === 'engineer' && $user->is_active && ! $wasActiveEngineer) {
            if (self::activeEngineerCount($user->getKey()) >= self::MAX_ACTIVE_ENGINEERS) {
                $user->is_active = false;
                $notice = '4 engineer accounts are already active — this one was saved disabled. Disable another engineer to activate it.';
            }
        }

        $user->save();

        return ['user' => $user, 'notice' => $notice];
    }

    /**
     * Disable a user. This is how the system revokes -- a re-grant flips it back
     * rather than rebuilding the row.
     */
    public static function revoke(User $user): void
    {
        $user->is_active = false;
        $user->save();
    }

    /**
     * Re-enable a user. Returns a notice instead of enabling when the engineer
     * cap would be exceeded.
     */
    public static function reinstate(User $user): ?string
    {
        if ($user->role === 'engineer' && self::activeEngineerCount($user->getKey()) >= self::MAX_ACTIVE_ENGINEERS) {
            return 'Only 4 engineer accounts can be active at a time. Disable another engineer first.';
        }

        $user->is_active = true;
        $user->save();

        return null;
    }

    private static function activeEngineerCount(?int $excludeId = null): int
    {
        return User::query()
            ->where('role', 'engineer')
            ->where('is_active', true)
            ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
            ->count();
    }

    private static function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
