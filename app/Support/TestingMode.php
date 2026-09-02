<?php

namespace App\Support;

/**
 * Decides whether the system is a real deployment or still a work in progress.
 *
 * The signal is the Turnstile secret key, and nothing else: it only ever exists
 * on a system that has been properly set up for a real environment, so a blank
 * secret means the system isn't there yet. Filling the secret in *is* the flip
 * to a live login -- there is no flag to remember at go-live.
 */
class TestingMode
{
    public static function enabled(): bool
    {
        // A misconfigured production system must fail closed -- locked out --
        // rather than fall back to dummy accounts.
        if (app()->environment('production')) {
            return false;
        }

        return blank(config('services.turnstile.secret'));
    }
}
