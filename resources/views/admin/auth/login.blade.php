@extends('layouts.auth')
@section('title', 'Admin Login')
@section('content')
<div class="auth-heading"><h1>Welcome back</h1><p>Sign in to continue to your secure tracking workspace.</p></div>
<x-alert />
<form method="POST" action="{{ route('admin.login.store') }}" class="stack auth-stack" data-login-location>@csrf
<input type="hidden" name="latitude" value="">
<input type="hidden" name="longitude" value="">
<input type="hidden" name="location_status" value="unavailable">
<x-field name="email" label="Email address" type="email" placeholder="you@example.com" autocomplete="username" required autofocus />
<x-field name="password" label="Password" type="password" placeholder="Enter your password" autocomplete="current-password" toggleable required />
<div class="auth-options"><span class="muted"><x-icon name="lock" /> Secure sign-in</span><a href="{{ route('admin.password.request') }}">Forgot password?</a></div>
<p class="login-location-note" id="login-location-note" aria-live="polite">Location is requested when you sign in. You can continue if it is unavailable.</p>
<x-button class="btn-full">Sign in to workspace <x-icon name="arrow" /></x-button>
</form>
@endsection
