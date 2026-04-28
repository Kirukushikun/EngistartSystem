<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 3;

    private const LOCKOUT_TIME = 900;

    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route($this->homeRouteForRole((string) Auth::user()->role));
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $mode = (string) config('auth.mode', 'local');

        return $mode === 'api'
            ? $this->attemptApiLogin($request, $credentials)
            : $this->attemptLocalLogin($request, $credentials);
    }

    protected function attemptLocalLogin(Request $request, array $credentials): RedirectResponse
    {
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The provided credentials do not match our records.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route($this->homeRouteForRole((string) $request->user()->role));
    }

    protected function attemptApiLogin(Request $request, array $credentials): RedirectResponse
    {
        $email = (string) $credentials['email'];

        if ($this->isLocked($email)) {
            $remainingMinutes = $this->getRemainingLockoutTime($email);

            return back()
                ->withErrors([
                    'email' => "Account temporarily locked due to multiple failed attempts. Please try again in {$remainingMinutes} minute(s).",
                ])
                ->onlyInput('email');
        }

        try {
            $baseUri = rtrim((string) config('auth.api.base_uri'), '/');
            $apiKey = (string) config('auth.api.api_key');
            $authUserApiKey = (string) config('auth.api.auth_user_api_key');
            $verify = config('auth.api.verify', true);

            if ($baseUri === '' || $apiKey === '' || $authUserApiKey === '') {
                return back()
                    ->withErrors(['email' => 'API authentication is enabled but auth configuration is incomplete.'])
                    ->onlyInput('email');
            }

            $authResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->withOptions(['verify' => $verify])
                ->post($baseUri . '/api/v1/auth/login', [
                    'email' => $email,
                    'password' => (string) $credentials['password'],
                ]);

            if (! $authResponse->successful()) {
                $this->incrementAttempts($email);

                $attemptsLeft = self::MAX_ATTEMPTS - $this->getAttempts($email);
                $errorMessage = $authResponse->json('message') ?: 'Incorrect username or password.';

                if ($attemptsLeft > 0) {
                    $errorMessage .= " You have {$attemptsLeft} attempt(s) remaining.";
                }

                return back()
                    ->withErrors(['email' => $errorMessage])
                    ->onlyInput('email');
            }

            $authPayload = $authResponse->json();

            session([
                'auth_token' => $authPayload['token'] ?? null,
                'token_expires' => $authPayload['expires_at'] ?? null,
                'email' => $authPayload['email'] ?? $email,
            ]);

            $userResponse = Http::withHeaders([
                'x-api-key' => $authUserApiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->withOptions(['verify' => $verify])
                ->get($baseUri . '/api/v1/users/get-user-id', [
                    'email' => $email,
                ]);

            if (! $userResponse->successful()) {
                $this->incrementAttempts($email);

                return back()
                    ->withErrors(['email' => 'Failed to retrieve user information from the authenticator.'])
                    ->onlyInput('email');
            }

            $resolvedUserId = $this->resolveExternalUserId($userResponse->json());
            $user = $resolvedUserId ? User::find($resolvedUserId) : null;

            if (! $user) {
                $this->incrementAttempts($email);

                return back()
                    ->withErrors(['email' => 'You are authenticated externally but not authorized to access this system.'])
                    ->onlyInput('email');
            }

            $this->clearAttempts($email);
            Auth::loginUsingId($user->id, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->route($this->homeRouteForRole((string) $user->role));
        } catch (\Throwable $exception) {
            $this->incrementAttempts($email);

            return back()
                ->withErrors(['email' => 'Authentication failed. ' . $exception->getMessage()])
                ->onlyInput('email');
        }
    }

    protected function resolveExternalUserId(mixed $payload): ?int
    {
        $encryptedId = data_get($payload, 'id')
            ?? data_get($payload, 'data.id')
            ?? data_get($payload, 'user.id');

        if ($encryptedId === null || $encryptedId === '') {
            return null;
        }

        if (is_numeric($encryptedId)) {
            return (int) $encryptedId;
        }

        try {
            return (int) Crypt::decryptString((string) $encryptedId);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function isLocked(string $email): bool
    {
        return Cache::has($this->getLockoutKey($email));
    }

    protected function getAttempts(string $email): int
    {
        return (int) Cache::get($this->getAttemptsKey($email), 0);
    }

    protected function incrementAttempts(string $email): void
    {
        $key = $this->getAttemptsKey($email);
        $attempts = $this->getAttempts($email) + 1;

        Cache::put($key, $attempts, now()->addSeconds(self::LOCKOUT_TIME));

        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::put($this->getLockoutKey($email), now()->addSeconds(self::LOCKOUT_TIME)->timestamp, now()->addSeconds(self::LOCKOUT_TIME));
        }
    }

    protected function clearAttempts(string $email): void
    {
        Cache::forget($this->getAttemptsKey($email));
        Cache::forget($this->getLockoutKey($email));
    }

    protected function getRemainingLockoutTime(string $email): int
    {
        $expiresAt = (int) Cache::get($this->getLockoutKey($email), 0);

        if ($expiresAt <= 0) {
            return (int) ceil(self::LOCKOUT_TIME / 60);
        }

        return max(1, (int) ceil(($expiresAt - now()->timestamp) / 60));
    }

    protected function getAttemptsKey(string $email): string
    {
        return 'engistart_login_attempts_' . sha1(mb_strtolower($email));
    }

    protected function getLockoutKey(string $email): string
    {
        return 'engistart_login_lockout_' . sha1(mb_strtolower($email));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public static function homeRouteForRole(string $role): string
    {
        return match ($role) {
            'farm_manager' => 'farm-manager.requests.new',
            'division_head' => 'division-head.inbox',
            'vp_gen_services' => 'vp-gen-services.inbox',
            'dh_gen_services' => 'dh-gen-services.noting',
            'ed_manager' => 'ed-manager.inbox',
            'it_admin' => 'it-admin.all-requests',
            default => 'guest.finished-requests',
        };
    }
}
