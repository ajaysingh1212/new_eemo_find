<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light"><title>@yield('title', 'Dashboard') · Eemo-Find</title>@include('layouts.assets')<link rel="stylesheet" href="{{ asset('assets/admin-theme.css') }}"></head>
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
<a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif><x-icon name="grid" /><span>Dashboard</span></a>
<a href="{{ route('admin.profile') }}" class="nav-link {{ request()->routeIs('admin.profile*') ? 'active' : '' }}" @if(request()->routeIs('admin.profile*')) aria-current="page" @endif><x-icon name="user" /><span>My Profile</span></a>
<button class="nav-link security-toggle {{ request()->routeIs('admin.security.*') ? 'section-active' : '' }}" id="security-toggle" aria-controls="security-submenu" aria-expanded="{{ request()->routeIs('admin.security.*') ? 'true' : 'false' }}"><x-icon name="shield" /><span>Security</span><x-icon name="chevron" class="chevron" /></button>
<div id="security-submenu" class="submenu" @if(!request()->routeIs('admin.security.*')) hidden @endif>
<a href="{{ route('admin.security.activity-logs.index') }}" class="nav-link {{ request()->routeIs('admin.security.activity-logs.*') ? 'active' : '' }}" @if(request()->routeIs('admin.security.activity-logs.*')) aria-current="page" @endif><x-icon name="activity" /><span>Activity Log</span></a>
<a href="{{ route('admin.security.active-sessions.index') }}" class="nav-link {{ request()->routeIs('admin.security.active-sessions.*') ? 'active' : '' }}" @if(request()->routeIs('admin.security.active-sessions.*')) aria-current="page" @endif><x-icon name="device" /><span>Active Sessions</span></a>
</div></nav>
<div class="sidebar-bottom">
<div class="sidebar-admin"><x-avatar :admin="auth('admin')->user()" /><div><strong>{{ auth('admin')->user()->name }}</strong><small>{{ auth('admin')->user()->is_super_admin ? 'Super Admin' : 'Administrator' }}</small><small class="sidebar-admin-ip" title="IP address: {{ request()->ip() ?? 'Unavailable' }}">IP {{ request()->ip() ?? 'Unavailable' }}</small></div></div>
</div></aside>
<div class="main-shell">
<header class="topbar">
<div class="topbar-start"><button class="icon-button" id="sidebar-toggle" aria-label="Toggle sidebar" aria-controls="sidebar" aria-expanded="true"><x-icon name="menu" /></button>
<form class="topbar-search" method="GET" action="{{ route('admin.security.activity-logs.index') }}" role="search">
    <x-icon name="search" /><label class="sr-only" for="workspace-search">Search activity logs</label>
    <input id="workspace-search" name="search" type="search" placeholder="Search your activity…" maxlength="120" value="{{ request()->routeIs('admin.security.activity-logs.index') ? request('search') : '' }}">
    <button type="submit" aria-label="Search activity logs"><x-icon name="arrow" /></button>
</form></div>
<div class="topbar-end"><span class="admin-only"><span class="status-dot"></span> Admin access</span><span class="topbar-divider"></span><x-avatar :admin="auth('admin')->user()" /><span class="topbar-name">{{ auth('admin')->user()->name }}</span><form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="icon-button" aria-label="Sign out"><x-icon name="logout" /></button></form></div>
</header>
<main id="main" class="main-content container-fluid" tabindex="-1"><nav class="breadcrumbs" aria-label="Breadcrumb"><span>Workspace</span><span class="separator">/</span>@if(request()->routeIs('admin.security.*'))<span>Security</span><span class="separator">/</span>@endif<span aria-current="page">@yield('title', 'Dashboard')</span></nav><x-alert />@yield('content')</main>
<footer class="footer"><span>© {{ now()->year }} Eemo-Find</span><span>Administrator workspace <span class="footer-dot">·</span> Private &amp; secure</span></footer>
</div><x-modal />
</body></html>
