<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
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

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The provided credentials do not match our records.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route($this->homeRouteForRole((string) $request->user()->role));
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
            'dh_gen_services' => 'dh-gen-services.late-filings',
            'ed_manager' => 'ed-manager.inbox',
            'it_admin' => 'it-admin.all-requests',
            default => 'guest.finished-requests',
        };
    }
}
