@extends('layouts.admin')

@section('title', 'User Details')

@section('content')
@php($device = $user->devices->first())
<section class="user-detail-page">
    <a class="user-detail-back" href="{{ route('admin.user-management.devices') }}"><x-icon name="arrow" /> Back to devices</a>

    <header class="user-detail-hero">
        <div class="user-detail-avatar-wrap">
            @if ($user->profile_image)
                <img class="user-detail-avatar" src="{{ Storage::disk('public')->url($user->profile_image) }}" alt="{{ $user->name }}">
            @else
                <span class="user-detail-avatar user-detail-initials">{{ collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('') ?: 'U' }}</span>
            @endif
        </div>
        <div class="user-detail-identity">
            <h1>{{ $user->name }}</h1>
            <span class="user-detail-code">{{ $user->user_code }}</span>
            <div class="user-detail-contact">
                <p>{{ $user->email }}</p>
                <p>{{ $user->phone }}</p>
                <p>{{ $user->address ?: 'Address not provided' }}</p>
            </div>
        </div>
        <span class="registered-user-status {{ $user->status === 'active' ? 'is-active' : 'is-inactive' }}">{{ ucfirst($user->status) }}</span>
    </header>

    <div class="user-detail-layout">
        <div class="user-detail-main">
            <section class="user-detail-panel">
                <div class="user-detail-panel-heading">
                    <div><span class="eyebrow">DEVICE INFORMATION</span><h2>Device details</h2></div>
                    <span class="user-detail-heading-icon device-heading-icon"><x-icon name="device" /></span>
                </div>
                <dl class="user-detail-list">
                    @if ($device)
                        <div><dt>Device name</dt><dd>Unknown</dd></div>
                        <div><dt>Manufacturer / Brand</dt><dd>{{ $device->manufacturer ?: 'Unknown' }}</dd></div>
                        <div><dt>Device model</dt><dd>{{ $device->device_model ?: 'Unknown' }}</dd></div>
                        <div><dt>Device ID / UUID</dt><dd class="user-detail-mono">{{ str_starts_with($device->device_uuid ?? '', 'Pending-') ? 'Unknown' : ($device->device_uuid ?: 'Unknown') }}</dd></div>
                        <div><dt>Firmware version</dt><dd>{{ $device->firmware_version ?: 'Not available' }}</dd></div>
                        <div><dt>Network</dt><dd>Unknown</dd></div>
                        <div><dt>GPS status</dt><dd>Unknown</dd></div>
                        <div><dt>Battery</dt><dd>Unknown</dd></div>
                        <div><dt>Last update</dt><dd>Unknown</dd></div>
                    @else
                        <div class="user-detail-device-empty-row">
                            <dt>Device status</dt><dd>No device connected</dd>
                        </div>
                    @endif
                </dl>
            </section>
        </div>

        <aside class="user-detail-device-column">
            <section class="user-detail-device-panel">
                <div class="user-detail-panel-heading">
                    <div><span class="eyebrow">CONNECTED DEVICE</span><h2>Device</h2></div>
                    <span class="user-detail-heading-icon device-heading-icon"><x-icon name="device" /></span>
                </div>

                @if ($device)
                    <div class="user-detail-phone" aria-label="Phone preview for {{ $user->name }}">
                        <div class="user-detail-phone-screen">
                            <span class="user-detail-phone-notch"></span>
                            <div class="user-detail-phone-topbar"><span><x-icon name="map-pin" /> Eemo-Find</span><i><b></b><b></b><b></b></i></div>
                            <div class="user-detail-phone-map"><span class="phone-map-road road-one"></span><span class="phone-map-road road-two"></span><span class="phone-map-park"></span><i class="phone-map-pin"><x-icon name="map-pin" /></i></div>
                            <div class="user-detail-phone-profile">
                                @if ($user->profile_image)
                                    <img src="{{ Storage::disk('public')->url($user->profile_image) }}" alt="">
                                @else
                                    <span class="user-detail-phone-initials">{{ collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('') ?: 'U' }}</span>
                                @endif
                                <strong>{{ $user->name }}</strong>
                                <span>{{ $user->user_code }}</span>
                            </div>
                            <div class="user-detail-phone-location"><span><i></i> Current location</span><strong>Unknown</strong><small>Location not available</small></div>
                            <div class="user-detail-phone-device">
                                <span class="user-detail-phone-device-icon"><x-icon name="device" /></span>
                                <span><small>{{ $device->manufacturer ?: 'Unknown' }}</small><strong>{{ $device->device_model ?: 'Unknown' }}</strong></span>
                                <span class="user-detail-phone-online"><i></i>Unknown</span>
                            </div>
                            <span class="user-detail-phone-home"></span>
                        </div>
                    </div>
                @else
                    <div class="user-detail-no-device">
                        <span><x-icon name="device" /></span>
                        <strong>No device connected</strong>
                        <p>This user does not have a registered device yet.</p>
                    </div>
                @endif
            </section>
        </aside>
    </div>
</section>
@endsection
