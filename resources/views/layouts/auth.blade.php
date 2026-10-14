<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light"><title>@yield('title') · Eemo-Find</title>@include('layouts.assets')</head>
<body class="auth-shell @if(request()->routeIs('admin.login')) auth-login-page @endif">
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
<div class="auth-panel">
    <aside class="auth-story">
        <x-brand />
        <div class="auth-story-content">
            <span class="eyebrow">SAFETY-FIRST TRACKING</span>
            <h1>Track every move.<br><span>Protect every decision.</span></h1>
            <p>Monitor activity, safeguard operations, and manage every secure event from one smart admin hub built for modern tracking tools.</p>
            <div class="auth-visual" aria-hidden="true">
                <div class="orbit orbit-one"></div>
                <div class="orbit orbit-two"></div>
                <span class="vault"><x-icon name="shield" /></span>
                <span class="visual-tag tag-one"><x-icon name="lock" /> Protected access</span>
                <span class="visual-tag tag-two"><span class="status-dot"></span> Live monitoring</span>
            </div>
        </div>
        <div class="auth-story-footer">
            <span>Eemo-Find</span>
            <span>Built for administrators</span>
        </div>
    </aside>

    <main class="auth-main" id="main">
        <div class="auth-card">
            <div class="auth-mobile-brand"><x-brand /></div>
            <div class="auth-form">
                <div class="auth-signature">
                    <span class="tile-icon"><x-icon name="lock" /></span>
                    <div>
                        <strong>Secure sign-in</strong>
                        <small>Welcome back</small>
                    </div>
                </div>
                @yield('content')
            </div>
            <div class="auth-restriction"><x-icon name="shield" /><span>This workspace is for authorized administrators only.<br>Contact your administrator if you need access.</span></div>
        </div>
        <footer>© {{ now()->year }} Eemo-Find. All rights reserved. Developed by <strong><a href="https://www.sanketkumarofficial.in/" target="_blank" rel="noopener noreferrer">Sanket Kumar</a></strong>.</footer>
    </main>
</div>
</body>
</html>
