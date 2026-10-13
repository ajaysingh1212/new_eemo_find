@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<section class="traffic-map" aria-label="GPS tracking map of India">
    <div class="traffic-map-canvas" id="admin-traffic-map" data-api-key="{{ config('services.google_maps.api_key') }}">
        <p class="traffic-map-status" id="traffic-map-status" role="status" aria-live="polite">Loading map…</p>
    </div>
    <div class="traffic-metrics" aria-label="GPS devices, licenses and users">
        <article class="traffic-metric device-metric">
            <div class="traffic-metric-heading"><h2>Total devices</h2><span class="traffic-metric-icon"><x-icon name="device" /></span></div>
            <strong class="traffic-metric-value">1,284</strong>
            <p><span class="metric-detail"><span class="metric-trend up">●</span> 1,126 active</span><span class="metric-detail"><span class="metric-trend down">●</span> 158 inactive</span></p>
        </article>
        <article class="traffic-metric license-metric">
            <div class="traffic-metric-heading"><h2>User licenses</h2><span class="traffic-metric-icon"><x-icon name="shield" /></span></div>
            <strong class="traffic-metric-value">620</strong>
            <p><span class="metric-detail"><span class="metric-trend up">●</span> 572 active</span><span class="metric-detail"><span class="metric-trend down">●</span> 48 expired</span></p>
        </article>
        <article class="traffic-metric online-metric">
            <div class="traffic-metric-heading"><h2>Users online</h2><span class="traffic-metric-icon"><x-icon name="user" /></span></div>
            <strong class="traffic-metric-value">184</strong>
            <p><span class="metric-trend up">●</span> Currently connected</p>
        </article>
        <article class="traffic-metric offline-metric">
            <div class="traffic-metric-heading"><h2>Users offline</h2><span class="traffic-metric-icon"><x-icon name="user" /></span></div>
            <strong class="traffic-metric-value">936</strong>
            <p><span class="metric-trend neutral">●</span> Not currently connected</p>
        </article>
    </div>
</section>
@endsection
