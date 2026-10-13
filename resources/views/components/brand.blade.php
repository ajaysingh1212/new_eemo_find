<a class="brand" href="{{ auth('admin')->check() ? route('admin.dashboard') : route('admin.login') }}" aria-label="Eemo-Find home">
    @if($logoPath)<img class="brand-image" src="{{ Storage::disk('public')->url($logoPath) }}" alt="Eemo-Find logo">
    @else<span class="brand-symbol"><x-icon name="brand" /></span>@endif
    <span class="brand-text">Eemo<span>-Find</span>@if(auth('admin')->check())<small class="brand-live-clock"><time data-clock data-timezone="{{ config('app.timezone') }}" datetime="{{ now()->toIso8601String() }}">{{ now()->format('d M Y H:i:s') }}</time></small>@else<small>ADMIN WORKSPACE</small>@endif</span>
</a>
