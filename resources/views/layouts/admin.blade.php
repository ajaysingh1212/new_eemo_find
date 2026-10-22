<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title', 'Dashboard') · Eemo-Find</title>@include('layouts.assets', ['adminTheme' => true])</head>
<body class="admin-shell">
<svg class="auth-tech-bg" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
    <defs>
        <pattern id="auth-grid" width="48" height="48" patternUnits="userSpaceOnUse">
            <path d="M48 0H0V48" fill="none" stroke="#4b86d8" stroke-opacity=".12" stroke-width="1" />
        </pattern>
        <radialGradient id="auth-radar">
            <stop offset="0" stop-color="#398bff" stop-opacity=".2" />
            <stop offset="1" stop-color="#398bff" stop-opacity="0" />
        </radialGradient>
    </defs>
    <rect width="1440" height="900" fill="url(#auth-grid)" />
    <circle cx="1120" cy="190" r="270" fill="url(#auth-radar)" />
    <g class="auth-circuit" fill="none" stroke="#4388df" stroke-linecap="round" stroke-linejoin="round">
        <path d="M0 620h180l54-54h112l42-42h108" />
        <path d="M1440 270h-160l-54 54h-94l-42 42h-90" />
        <path d="M980 900v-118l64-64v-82l52-52v-88" />
        <path d="M310 0v108l56 56v82l48 48v78" />
        <path class="auth-circuit-flow" d="M0 620h180l54-54h112l42-42h108" />
        <path class="auth-circuit-flow auth-circuit-flow-alt" d="M1440 270h-160l-54 54h-94l-42 42h-90" />
    </g>
    <g class="auth-signal">
        <circle cx="496" cy="524" r="4" />
        <circle cx="1000" cy="408" r="4" />
        <circle cx="466" cy="372" r="3" />
        <circle cx="1096" cy="584" r="3" />
    </g>
</svg>
<a href="#main" class="skip-link">Skip to content</a>
<div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
<aside class="sidebar" id="sidebar" aria-label="Main navigation">
<div class="sidebar-brand"><x-brand /></div>
<div class="nav-label">WORKSPACE</div>
<nav class="nav">
<a href="{{ route('admin.dashboard') }}" class="nav-link tone-blue {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif><x-icon name="grid" /><span>Dashboard</span></a>
<a href="{{ route('admin.notifications') }}" class="nav-link tone-rose {{ request()->routeIs('admin.notifications') ? 'active' : '' }}" @if(request()->routeIs('admin.notifications')) aria-current="page" @endif><x-icon name="bell" /><span>Notification</span></a>
<a href="{{ route('admin.user-management.devices') }}" class="nav-link tone-cyan {{ request()->routeIs('admin.user-management.*') ? 'active' : '' }}" @if(request()->routeIs('admin.user-management.*')) aria-current="page" @endif><x-icon name="device" /><span>User &amp; Device</span></a>
<button class="nav-link tone-cyan submenu-toggle {{ request()->routeIs('admin.tracking.*') ? 'section-active' : '' }}" id="tracking-toggle" data-submenu-toggle="tracking-submenu" aria-controls="tracking-submenu" aria-expanded="{{ request()->routeIs('admin.tracking.*') ? 'true' : 'false' }}"><x-icon name="activity" /><span>Tracking</span><x-icon name="chevron" class="chevron" /></button>
<div id="tracking-submenu" class="submenu" @if(!request()->routeIs('admin.tracking.*')) hidden @endif>
<a href="{{ route('admin.tracking.live-location') }}" class="nav-link tone-teal {{ request()->routeIs('admin.tracking.live-location') ? 'active' : '' }}" @if(request()->routeIs('admin.tracking.live-location')) aria-current="page" @endif><x-icon name="map-pin" /><span>Live &amp; History</span></a>
<a href="{{ route('admin.tracking.geo-fence') }}" class="nav-link tone-amber {{ request()->routeIs('admin.tracking.geo-fence') ? 'active' : '' }}" @if(request()->routeIs('admin.tracking.geo-fence')) aria-current="page" @endif><x-icon name="shield" /><span>Geofence</span></a>
<a href="{{ route('admin.tracking.task') }}" class="nav-link tone-green {{ request()->routeIs('admin.tracking.task') ? 'active' : '' }}" @if(request()->routeIs('admin.tracking.task')) aria-current="page" @endif><x-icon name="check" /><span>Task</span></a>
<a href="{{ route('admin.tracking.sos') }}" class="nav-link tone-rose {{ request()->routeIs('admin.tracking.sos') ? 'active' : '' }}" @if(request()->routeIs('admin.tracking.sos')) aria-current="page" @endif><x-icon name="bell" /><span>SOS &amp; Alert</span></a>
</div>
<a href="{{ route('admin.groups') }}" class="nav-link tone-rose {{ request()->routeIs('admin.groups') ? 'active' : '' }}" @if(request()->routeIs('admin.groups')) aria-current="page" @endif><x-icon name="users" /><span>Group</span></a>
<button class="nav-link tone-amber submenu-toggle {{ request()->routeIs('admin.licence.*') ? 'section-active' : '' }}" id="licence-toggle" data-submenu-toggle="licence-submenu" aria-controls="licence-submenu" aria-expanded="{{ request()->routeIs('admin.licence.*') ? 'true' : 'false' }}"><x-icon name="certificate" /><span>Licence</span><x-icon name="chevron" class="chevron" /></button>
<div id="licence-submenu" class="submenu" @if(!request()->routeIs('admin.licence.*')) hidden @endif>
<a href="{{ route('admin.licence.subscription') }}" class="nav-link tone-violet {{ request()->routeIs('admin.licence.subscription') ? 'active' : '' }}" @if(request()->routeIs('admin.licence.subscription')) aria-current="page" @endif><x-icon name="check" /><span>Subscription</span></a>
<a href="{{ route('admin.licence.payments') }}" class="nav-link tone-green {{ request()->routeIs('admin.licence.payments') ? 'active' : '' }}" @if(request()->routeIs('admin.licence.payments')) aria-current="page" @endif><x-icon name="credit-card" /><span>Payments</span></a>
</div>
<a href="{{ route('admin.support') }}" class="nav-link tone-teal {{ request()->routeIs('admin.support') ? 'active' : '' }}" @if(request()->routeIs('admin.support')) aria-current="page" @endif><x-icon name="headset" /><span>Support</span></a>
<button class="nav-link tone-rose security-toggle submenu-toggle {{ request()->routeIs('admin.security.active-sessions.*', 'admin.profile*') ? 'section-active' : '' }}" id="security-toggle" data-submenu-toggle="security-submenu" aria-controls="security-submenu" aria-expanded="{{ request()->routeIs('admin.security.active-sessions.*', 'admin.profile*') ? 'true' : 'false' }}"><x-icon name="shield" /><span>Security</span><x-icon name="chevron" class="chevron" /></button>
<div id="security-submenu" class="submenu" @if(!request()->routeIs('admin.security.active-sessions.*', 'admin.profile*')) hidden @endif>
<a href="{{ route('admin.profile') }}" class="nav-link tone-violet {{ request()->routeIs('admin.profile*') ? 'active' : '' }}" @if(request()->routeIs('admin.profile*')) aria-current="page" @endif><x-icon name="user" /><span>Admin Profile</span></a>
<a href="{{ route('admin.security.active-sessions.index') }}" class="nav-link tone-cyan {{ request()->routeIs('admin.security.active-sessions.*') ? 'active' : '' }}" @if(request()->routeIs('admin.security.active-sessions.*')) aria-current="page" @endif><x-icon name="device" /><span>Active Sessions</span></a>
</div>
<button class="nav-link tone-green developer-toggle submenu-toggle {{ request()->routeIs('admin.security.developer-mode', 'admin.developer.error-log', 'admin.developer.activity-logs.*', 'admin.developer.mobile-app', 'admin.developer.settings') ? 'section-active' : '' }}" id="developer-toggle" data-submenu-toggle="developer-submenu" aria-controls="developer-submenu" aria-expanded="{{ request()->routeIs('admin.security.developer-mode', 'admin.developer.error-log', 'admin.developer.activity-logs.*', 'admin.developer.mobile-app', 'admin.developer.settings') ? 'true' : 'false' }}"><x-icon name="cpu" /><span>Developer</span><x-icon name="chevron" class="chevron" /></button>
<div id="developer-submenu" class="submenu" @if(!request()->routeIs('admin.security.developer-mode', 'admin.developer.error-log', 'admin.developer.activity-logs.*', 'admin.developer.mobile-app', 'admin.developer.settings')) hidden @endif>
<a href="{{ route('admin.developer.settings') }}" class="nav-link tone-violet {{ request()->routeIs('admin.developer.settings') ? 'active' : '' }}" @if(request()->routeIs('admin.developer.settings')) aria-current="page" @endif><x-icon name="settings" /><span>Settings</span></a>
<a href="{{ route('admin.developer.mobile-app') }}" class="nav-link tone-cyan {{ request()->routeIs('admin.developer.mobile-app') ? 'active' : '' }}" @if(request()->routeIs('admin.developer.mobile-app')) aria-current="page" @endif><x-icon name="device" /><span>Mobile App</span></a>
<a href="{{ route('admin.developer.activity-logs.index') }}" class="nav-link tone-blue {{ request()->routeIs('admin.developer.activity-logs.*') ? 'active' : '' }}" @if(request()->routeIs('admin.developer.activity-logs.*')) aria-current="page" @endif><x-icon name="activity" /><span>Activity Log</span></a>
<a href="{{ route('admin.developer.error-log') }}" class="nav-link tone-rose {{ request()->routeIs('admin.developer.error-log') ? 'active' : '' }}" @if(request()->routeIs('admin.developer.error-log')) aria-current="page" @endif><x-icon name="activity" /><span>Error Log</span></a>
</div>
</nav>
<div class="sidebar-bottom">
<div class="sidebar-admin"><x-avatar :admin="auth('admin')->user()" /><div><strong>{{ auth('admin')->user()->name }}</strong><small>{{ auth('admin')->user()->is_super_admin ? 'Super Admin' : 'Administrator' }}</small><small class="sidebar-admin-ip" title="IP address: {{ request()->ip() ?? 'Unavailable' }}">IP {{ request()->ip() ?? 'Unavailable' }}</small></div></div>
</div></aside>
<div class="main-shell">
<header class="topbar">
<div class="topbar-start"><button class="icon-button" id="sidebar-toggle" aria-label="Toggle sidebar" aria-controls="sidebar" aria-expanded="true"><x-icon name="menu" /></button></div>
<div class="topbar-end">
<div class="admin-weather" id="admin-weather" data-location-url="{{ route('admin.session.location') }}" data-latitude="{{ session('admin_location.latitude', '') }}" data-longitude="{{ session('admin_location.longitude', '') }}" data-permission-status="{{ session('admin_location.permission_status', '') }}">
    <span class="admin-weather-icon" id="admin-weather-icon" aria-hidden="true"><x-icon name="sun" /><x-icon name="cloud" hidden /><x-icon name="cloud-rain" hidden /></span>
    <span class="admin-weather-copy"><strong id="admin-weather-temperature">--°</strong><small id="admin-weather-condition">Location not set</small></span>
    <span class="admin-weather-metrics" aria-label="Current weather details">
        <span><strong id="admin-weather-feels-like">--°</strong><small>Feels</small></span>
        <span><strong id="admin-weather-humidity">--%</strong><small>Humidity</small></span>
        <span><strong id="admin-weather-wind">-- km/h</strong><small>Wind</small></span>
    </span>
    <span class="admin-weather-location-divider" aria-hidden="true"></span>
    <button type="button" class="icon-button admin-location-button" id="admin-location-button" aria-label="Refresh location and weather" title="Refresh location and weather"><x-icon name="refresh" /></button>
    <span class="admin-weather-location">
        <strong id="admin-weather-coordinates">{{ session()->has('admin_location.latitude') && session()->has('admin_location.longitude') ? number_format((float) session('admin_location.latitude'), 4).'°, '.number_format((float) session('admin_location.longitude'), 4).'°' : 'Location not set' }}</strong>
        <small>IP {{ request()->ip() ?? 'Unavailable' }}</small>
    </span>
    <span class="sr-only" id="admin-weather-status" aria-live="polite"></span>
</div>
<span class="topbar-divider"></span><x-avatar :admin="auth('admin')->user()" /><span class="topbar-name">{{ auth('admin')->user()->name }}</span><form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="icon-button" aria-label="Sign out"><x-icon name="logout" /></button></form></div>
</header>
<main id="main" class="main-content container-fluid" tabindex="-1"><nav class="breadcrumbs" aria-label="Breadcrumb"><span>Workspace</span><span class="separator">/</span>@if(request()->routeIs('admin.security.*'))<span>Security</span><span class="separator">/</span>@elseif(request()->routeIs('admin.developer.*'))<span>Developer</span><span class="separator">/</span>@endif<span aria-current="page">@yield('title', 'Dashboard')</span></nav><x-alert />@yield('content')</main>
<footer class="footer"><span>© {{ now()->year }} Eemo-Find</span><span>Administrator workspace <span class="footer-dot">·</span> Private &amp; secure</span><span>Developed by <strong><a href="https://www.sanketkumarofficial.in/" target="_blank" rel="noopener noreferrer">Sanket Kumar</a></strong></span></footer>
</div><x-modal />
</body></html>
