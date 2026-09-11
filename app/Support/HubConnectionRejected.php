<?php

namespace App\Support;

use RuntimeException;

/**
 * The hub rejected the stored connection outright (401/403) -- wrong secret
 * or a revoked connection. Distinct from a generic outage so the UI can offer
 * "reset connection" instead of "try again later" (guide section 6: "Do not
 * silently retry").
 */
class HubConnectionRejected extends RuntimeException
{
}
