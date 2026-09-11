<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The Access Hub connection this system authenticates sync calls with.
 *
 * Guide section 4.3: one row, never in .env, client_secret encrypted at rest.
 * Created by App\Support\HubClient::enroll(), read by ::fetchGrants().
 */
class HubConnection extends Model
{
    protected $fillable = [
        'client_id',
        'client_secret',
        'last_synced_at',
    ];

    protected $hidden = [
        'client_secret',
    ];

    protected function casts(): array
    {
        return [
            'client_secret' => 'encrypted',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * There is only ever one connection.
     */
    public static function current(): ?self
    {
        return static::query()->latest('id')->first();
    }

    /**
     * Enrollment replaces any prior connection outright -- there is no
     * "update the secret" path, only enroll-fresh or reset-and-re-enroll.
     */
    public static function replace(string $clientId, string $clientSecret): self
    {
        static::reset();

        return static::create([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);
    }

    public static function reset(): void
    {
        static::query()->delete();
    }
}
