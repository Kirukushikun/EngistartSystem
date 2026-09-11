<?php

namespace App\Support;

use RuntimeException;

/**
 * Enrollment (POST /api/v1/enroll) failed -- bad/missing project key, a
 * wrong/expired/used-up code, rate limiting, or the hub being unreachable.
 * The message is written to be shown to the admin as-is.
 */
class HubEnrollmentException extends RuntimeException
{
}
