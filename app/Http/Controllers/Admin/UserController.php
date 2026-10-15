<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return $this->renderIndex($request);
    }

    public function devices(Request $request)
    {
        return $this->renderIndex($request, true);
    }

    private function renderIndex(Request $request, bool $isDevicePage = false)
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('user_code', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->appends(['q' => $search]);

        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $inactiveUsers = User::where('status', 'inactive')->count();

        return view('admin.user-management.users', compact('users', 'totalUsers', 'activeUsers', 'inactiveUsers', 'search', 'isDevicePage'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'string', 'min:8'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'phone.required' => 'Phone number is required.',
            'phone.unique' => 'This phone number is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        $profileImage = null;

        if ($request->hasFile('profile_image')) {
            $profileImage = $request->file('profile_image')->store('user-profiles', 'public');
        }

        User::create([
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'phone' => preg_replace('/\s+/', '', $validated['phone']),
            'address' => $validated['address'] ?? null,
            'password' => $validated['password'],
            'profile_image' => $profileImage,
            'status' => 'active',
        ]);

        return redirect()->route('admin.user-management.users')->with('status', 'User registered successfully.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'string', 'min:8'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if ($request->hasFile('profile_image')) {
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $validated['profile_image'] = $request->file('profile_image')->store('user-profiles', 'public');
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'phone' => preg_replace('/\s+/', '', $validated['phone']),
            'address' => $validated['address'] ?? null,
            'profile_image' => $validated['profile_image'] ?? $user->profile_image,
            'status' => $validated['status'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()->route('admin.user-management.users')->with('status', 'User details updated successfully.');
    }
}
