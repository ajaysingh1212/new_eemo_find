<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create()
    {
        return view('admin.auth.login');
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_status' => ['nullable', 'in:granted,denied,unavailable,error'],
        ]);
        $hasLatitude = ($credentials['latitude'] ?? null) !== null;
        $hasLongitude = ($credentials['longitude'] ?? null) !== null;
        if ($hasLatitude !== $hasLongitude) {
            throw ValidationException::withMessages(['location' => 'Location coordinates are incomplete.']);
        }
        $key = 'admin-login:'.hash('sha256', $credentials['email'].'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }
        if (! Auth::guard('admin')->attempt(['email' => $credentials['email'], 'password' => $credentials['password']], false)) {
            RateLimiter::hit($key, 60);
            // Failed attempts are deliberately unattributed; they do not reveal known emails.
            $logger->log('login.failed');
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $hasLocation = $hasLatitude && $hasLongitude;
        $location = [
            'permission_status' => $hasLocation ? 'granted' : ($credentials['location_status'] ?? 'unavailable'),
            'updated_at' => now()->toIso8601String(),
        ];
        if ($hasLocation) {
            $location['latitude'] = (float) $credentials['latitude'];
            $location['longitude'] = (float) $credentials['longitude'];
        }
        $request->session()->put('admin_location', $location);
        $logger->log('login.success', $request->user('admin'));

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request, ActivityLogger $logger)
    {
        $logger->log('logout', $request->user('admin'));
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
