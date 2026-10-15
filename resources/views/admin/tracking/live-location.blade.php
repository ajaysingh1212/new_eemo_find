@extends('layouts.admin')

@section('title', 'Live Location')

@section('content')
<section class="live-location-page" aria-label="Live location maps">
    <div class="location-map-grid">
        <article class="location-map-card">
            <header class="location-map-card-header">
                <div>
                    <span class="location-map-kicker">CURRENT POSITION</span>
                    <h2><x-icon name="map-pin" /> Current location</h2>
                </div>
                <span class="location-live-badge"><i></i> Live</span>
            </header>
            <div class="location-map location-map-current" role="img" aria-label="Demo map showing a device's current location near India Gate, New Delhi">
                <svg class="map-art" viewBox="0 0 720 420" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                    <rect width="720" height="420" fill="#edf3e9"/>
                    <path class="map-park" d="M38 37h193l18 55-42 34-10 70-67 14-34-44-72-9zM487 25h190l14 102-66 28-27 67-74-12-32-63-52-28zM40 282l102-33 49 36-10 92H47zM510 282l154-37 39 70-27 72H533z"/>
                    <path class="map-water" d="M284-20c21 84 11 135-6 194s-9 122 22 266h39c-27-131-34-198-16-260s30-121 1-200z"/>
                    <g class="map-minor-roads">
                        <path d="M-20 92 745 334M-10 334 721 101M75-20l117 468M416-30 301 450M636-30 470 451M-30 205l773 17M104 22l557 392M-20 397 650 16"/>
                        <path d="m156 78 45 7 35 48-18 41 31 34-26 42m310-190-19 55 28 37-34 52 20 53m-435 42 74-33 40 17 27 67m277-23 48-43 60 7 39 40"/>
                    </g>
                    <g class="map-major-roads">
                        <path d="M-25 154c132 35 232 27 355 8s249-10 416 39M82-25c49 100 96 166 179 230s151 120 211 240M-10 373c137-91 234-169 335-245S527 29 732-5"/>
                    </g>
                    <g class="map-labels">
                        <text x="94" y="112">Chanakyapuri</text><text x="495" y="106">Connaught Place</text>
                        <text x="94" y="323">Lodhi Gardens</text><text x="498" y="331">Pragati Maidan</text>
                        <text x="320" y="79">NEW DELHI</text>
                    </g>
                    <circle class="map-radius" cx="365" cy="214" r="48"/>
                    <circle class="map-location-pulse" cx="365" cy="214" r="24"/>
                    <g class="map-location-pin" transform="translate(365 214)">
                        <circle r="17"/><path d="M0 26c-4-8-11-16-11-23a11 11 0 1 1 22 0c0 7-7 15-11 23Z"/><circle cy="2" r="3.5"/>
                    </g>
                </svg>
                <div class="map-zoom-controls" aria-hidden="true"><span>+</span><span>−</span></div>
                <div class="map-attribution">Demo map data</div>
                <div class="current-location-tooltip"><i></i><span><strong>Eemo Tracker</strong><small>Just now · Moving</small></span></div>
            </div>
            <footer class="location-map-footer">
                <div><span>Coordinates</span><strong>28.6129° N, 77.2295° E</strong></div>
                <div><span>Address</span><strong>India Gate, New Delhi</strong></div>
                <div><span>Speed</span><strong>32 km/h</strong></div>
            </footer>
        </article>

        <article class="location-map-card">
            <header class="location-map-card-header">
                <div>
                    <span class="location-map-kicker">TODAY · 15 OCT 2026</span>
                    <h2><x-icon name="activity" /> Route history</h2>
                </div>
                <span class="route-distance"><x-icon name="map-pin" /> 18.6 km</span>
            </header>
            <div class="location-map location-map-route" role="img" aria-label="Demo route history map showing a journey from India Gate through Lodhi Gardens to Connaught Place">
                <svg class="map-art" viewBox="0 0 720 420" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                    <rect width="720" height="420" fill="#edf3e9"/>
                    <path class="map-park" d="M38 37h193l18 55-42 34-10 70-67 14-34-44-72-9zM487 25h190l14 102-66 28-27 67-74-12-32-63-52-28zM40 282l102-33 49 36-10 92H47zM510 282l154-37 39 70-27 72H533z"/>
                    <path class="map-water" d="M284-20c21 84 11 135-6 194s-9 122 22 266h39c-27-131-34-198-16-260s30-121 1-200z"/>
                    <g class="map-minor-roads">
                        <path d="M-20 92 745 334M-10 334 721 101M75-20l117 468M416-30 301 450M636-30 470 451M-30 205l773 17M104 22l557 392M-20 397 650 16"/>
                        <path d="m156 78 45 7 35 48-18 41 31 34-26 42m310-190-19 55 28 37-34 52 20 53m-435 42 74-33 40 17 27 67m277-23 48-43 60 7 39 40"/>
                    </g>
                    <g class="map-major-roads">
                        <path d="M-25 154c132 35 232 27 355 8s249-10 416 39M82-25c49 100 96 166 179 230s151 120 211 240M-10 373c137-91 234-169 335-245S527 29 732-5"/>
                    </g>
                    <g class="map-labels">
                        <text x="94" y="112">Chanakyapuri</text><text x="495" y="106">Connaught Place</text>
                        <text x="94" y="323">Lodhi Gardens</text><text x="498" y="331">Pragati Maidan</text>
                        <text x="320" y="79">NEW DELHI</text>
                    </g>
                    <path class="route-halo" d="M145 321c32-53 82-74 117-48s41 36 79 12 47-67 93-61 51 37 80 14 28-63 65-78"/>
                    <path class="route-line" d="M145 321c32-53 82-74 117-48s41 36 79 12 47-67 93-61 51 37 80 14 28-63 65-78"/>
                    <g class="route-stop route-stop-start" transform="translate(145 321)"><circle r="12"/><circle r="4"/></g>
                    <g class="route-stop route-stop-mid" transform="translate(341 285)"><circle r="10"/><circle r="3"/></g>
                    <g class="route-stop route-stop-end" transform="translate(579 160)"><circle r="12"/><circle r="4"/></g>
                </svg>
                <div class="map-zoom-controls" aria-hidden="true"><span>+</span><span>−</span></div>
                <div class="map-attribution">Demo map data</div>
                <div class="route-stop-label route-start-label"><i></i><span><strong>Start</strong><small>09:15 AM</small></span></div>
                <div class="route-stop-label route-end-label"><i></i><span><strong>Latest</strong><small>11:24 AM</small></span></div>
            </div>
            <footer class="location-map-footer route-map-footer">
                <div><span>Duration</span><strong>2 hr 9 min</strong></div>
                <div><span>Stops</span><strong>3 locations</strong></div>
                <div><span>Last update</span><strong>11:24 AM</strong></div>
            </footer>
        </article>
    </div>
    <p class="location-demo-note"><x-icon name="activity" /> All location, speed and route details shown here are sample demo data.</p>
</section>
@endsection
