@extends('layouts.admin')
@section('title', 'Active Sessions')
@section('content')
<div class="page-heading"><div><span class="eyebrow">SECURITY / DEVICE ACCESS</span><h1>Active Sessions<span class="heading-dot">.</span></h1><p>See where your account is signed in and end any session you don’t recognize.</p></div><span class="pill"><x-icon name="device" /> {{ $admin->name }}</span></div>
@if(!$supported)
<x-card title="Session listing is unavailable"><div class="inline-note"><x-icon name="device" /><p>This application is currently using the {{ config('session.driver') }} session driver. Reliable session listing and individual revocation require the database session driver. Ask your operator to set SESSION_DRIVER=database and run the migrations.</p></div><p>You can still sign out securely using the sign-out button. Changing your password invalidates other sessions on their next request.</p></x-card>
@else
<div class="inline-note session-note"><x-icon name="shield" /><p>Only sessions for your account are shown. Location is available only when the browser shared it; IP and browser details can help identify sessions when location is unavailable.</p></div>
<x-card class="session-card" title="Signed-in devices" description="Review the location, IP address, browser, and recent activity for each session.">
@if($sessions->isEmpty())<div class="empty-state"><span class="tile-icon"><x-icon name="device" /></span><h3>No stored sessions yet</h3><p>Your current session is saved after this request. Refresh this page to see it.</p><a class="btn btn-secondary" href="{{ route('admin.security.active-sessions.index') }}">Refresh sessions</a></div>
@else
@foreach($sessions as $session)
<article class="active-session-card">
    <div class="active-session-details">
        <div class="active-session-heading">
            <span class="tile-icon {{ $session['current'] ? '' : 'neutral' }}"><x-icon name="device" /></span>
            <div><h3>{{ $session['current'] ? 'Current session' : 'Other session' }}</h3><small>{{ $admin->name }}</small></div>
            @if($session['current'])<span class="event-badge">This device</span>@endif
            <form method="POST" action="{{ route('admin.security.active-sessions.destroy') }}" data-confirm="{{ $session['current'] ? 'Sign out of this session?' : 'Sign out this device from your account?' }}">@csrf @method('DELETE')<input type="hidden" name="reference" value="{{ $session['reference'] }}"><button class="icon-button session-logout" type="submit" aria-label="Log out {{ $session['current'] ? 'current session' : 'other session' }}" title="Log out"><x-icon name="logout" /></button></form>
        </div>
        <dl class="active-session-meta">
            <div><dt>Location</dt><dd>@if($session['location'])
                {{ number_format($session['location']['latitude'], 5) }}, {{ number_format($session['location']['longitude'], 5) }}
                @else
                Location not shared by browser
                @endif</dd></div>
            <div><dt>IP address</dt><dd>{{ $session['ip'] ?: 'Unavailable' }}</dd></div>
            <div><dt>Browser / device</dt><dd class="agent-text">{{ $session['agent'] ?: 'Device information unavailable' }}</dd></div>
            <div><dt>Last activity</dt><dd><time datetime="{{ $session['last_activity']->toIso8601String() }}">{{ $session['last_activity']->format('d M Y, H:i') }}</time></dd></div>
        </dl>
    </div>
    <div class="active-session-map">
        @if($session['location'])
            <iframe title="Map of session location" loading="lazy" referrerpolicy="no-referrer" src="https://maps.google.com/maps?q={{ $session['location']['latitude'] }},{{ $session['location']['longitude'] }}&amp;z=14&amp;output=embed"></iframe>
        @else
            <div class="active-session-no-map"><x-icon name="map-pin" /><span>Precise location unavailable</span><small>Browser location permission was not shared for this session.</small></div>
        @endif
    </div>
</article>
@endforeach
@endif
</x-card>
@endif
<div class="workspace-note"><x-icon name="lock" /><span>Session identifiers and authentication tokens are kept private. Logging out a session invalidates its access on the server.</span></div>
@endsection
