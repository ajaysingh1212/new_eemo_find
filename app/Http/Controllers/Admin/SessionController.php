<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\AdminSessionService;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class SessionController extends Controller
{
    public function index(Request $request, AdminSessionService $service)
    {
        $supported = $service->supported();
        $sessions = $supported ? $service->query($request->user('admin'))
            ->where('last_activity', '>=', now()->subMinutes(config('session.lifetime'))->timestamp)
            ->orderByDesc('last_activity')->get()->map(function ($session) use ($request) {
                $location = $this->sessionLocation($session->payload);

                return [
                    'reference' => Crypt::encryptString($session->id),
                    'current' => hash_equals($request->session()->getId(), $session->id),
                    'ip' => $session->ip_address, 'agent' => $session->user_agent,
                    'last_activity' => Carbon::createFromTimestamp($session->last_activity),
                    'location' => $location,
                ];
            }) : collect();

        return view('admin.security.active-sessions', [
            'supported' => $supported,
            'sessions' => $sessions,
            'admin' => $request->user('admin'),
        ]);
    }

    public function location(Request $request)
    {
        $data = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'permission_status' => ['required', 'in:granted,denied,unavailable,error'],
        ]);
        $hasLatitude = ($data['latitude'] ?? null) !== null;
        $hasLongitude = ($data['longitude'] ?? null) !== null;
        if ($hasLatitude !== $hasLongitude) {
            return response()->json(['message' => 'Location coordinates are incomplete.'], 422);
        }

        $location = $request->session()->get('admin_location', []);
        if (isset($data['latitude'], $data['longitude'])) {
            $location['latitude'] = (float) $data['latitude'];
            $location['longitude'] = (float) $data['longitude'];
            $location['located_at'] = now()->toIso8601String();
            $data['permission_status'] = 'granted';
        }
        $location['permission_status'] = $data['permission_status'];
        $location['updated_at'] = now()->toIso8601String();
        $request->session()->put('admin_location', $location);

        return response()->json(['saved' => true]);
    }

    public function destroy(Request $request, AdminSessionService $service, ActivityLogger $logger)
    {
        $data = $request->validate(['reference' => ['required', 'string', 'max:2048']]);
        abort_unless($service->supported(), 409, 'Database sessions must be enabled.');
        try {
            $id = Crypt::decryptString($data['reference']);
        } catch (DecryptException $e) {
            abort(404);
        }
        $query = $service->query($request->user('admin'))->where('id', $id);
        abort_unless($query->exists(), 404);
        $current = hash_equals($request->session()->getId(), $id);
        $logger->log('session.revoked', $request->user('admin'), ['current_session' => $current]);
        $query->delete();
        if ($current) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->with('status', 'Session revoked. You have been signed out.');
        }

        return back()->with('status', 'Session revoked.');
    }

    private function sessionLocation(string $payload): array
    {
        $data = base64_decode($payload, true);
        if ($data === false) {
            return [];
        }

        if (config('session.encrypt')) {
            try {
                $data = Crypt::decrypt($data);
            } catch (DecryptException) {
                return [];
            }
        }

        $attributes = config('session.serialization', 'php') === 'json'
            ? json_decode($data, true)
            : @unserialize($data, ['allowed_classes' => false]);

        $location = is_array($attributes) ? ($attributes['admin_location'] ?? null) : null;
        if (! is_array($location)
            || ! isset($location['latitude'], $location['longitude'])
            || ! is_numeric($location['latitude'])
            || ! is_numeric($location['longitude'])) {
            return [];
        }

        $latitude = (float) $location['latitude'];
        $longitude = (float) $location['longitude'];
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return [];
        }

        return ['latitude' => $latitude, 'longitude' => $longitude];
    }
}
