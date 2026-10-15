@extends('layouts.admin')

@section('title', $isDevicePage ? 'Device Management' : 'User Management')

@section('content')
    @php
        $totalUserCount = $totalUsers ?? $users->total();
        $recordLabel = $isDevicePage ? 'device' : 'user';
        $recordPlural = $isDevicePage ? 'devices' : 'users';
    @endphp

    <section @class(['user-management', 'device-management' => $isDevicePage])>
        <div class="user-panel-header">
            <div class="user-stats">
                <article class="user-stat total-stat">
                    <span>Total {{ $recordPlural }}</span>
                    <strong>{{ $totalUserCount }}</strong>
                </article>
                <article class="user-stat active-stat">
                    <span>Active {{ $recordPlural }}</span>
                    <strong>{{ $activeUsers }}</strong>
                </article>
                <article class="user-stat inactive-stat">
                    <span>Inactive {{ $recordPlural }}</span>
                    <strong>{{ $inactiveUsers }}</strong>
                </article>
            </div>

            <div class="user-controls">
                <form method="GET" action="{{ $isDevicePage ? route('admin.user-management.devices') : route('admin.user-management.users') }}" class="user-search-form">
                    <span class="search-icon" aria-hidden="true">⌕</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search {{ $recordPlural }}..." aria-label="Search {{ $recordPlural }}">
                    <button type="submit" class="search-button">Search</button>
                </form>
                @unless ($isDevicePage)
                    <button type="button" class="primary-button" data-open-user-modal>
                        <x-icon name="user" /> Add user
                    </button>
                @endunless
            </div>
        </div>

        <div class="user-card-grid">
            @forelse ($users as $user)
                <article @class(['registered-user-card', 'device-card' => $isDevicePage])>
                    <div @class(['registered-user-top', 'device-owner-header' => $isDevicePage])>
                        <div class="registered-user-identity">
                            @if ($user->profile_image)
                                <img src="{{ Storage::disk('public')->url($user->profile_image) }}" alt="{{ $user->name }}" class="user-avatar">
                            @else
                                <span class="user-avatar initials">{{ collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('') ?: 'U' }}</span>
                            @endif
                            <div class="registered-user-name">
                                <h3>{{ $user->name }}</h3>
                                <span>{{ $user->user_code }}</span>
                            </div>
                        </div>
                        <span class="registered-user-status {{ $user->status === 'active' ? 'is-active' : 'is-inactive' }}">{{ ucfirst($user->status) }}</span>
                    </div>

                    @if ($isDevicePage)
                        <div class="device-phone" aria-hidden="true">
                            <div class="device-phone-screen">
                                <span class="device-phone-notch"></span>
                                <span class="device-phone-statusbar"><i></i><i></i><i></i></span>
                                <span class="device-phone-appmark"><x-icon name="map-pin" /></span>
                                <strong class="device-phone-appname">Eemo-Find</strong>
                                <span class="device-phone-caption">DEVICE TRACKING</span>
                                <span class="device-phone-map"><i></i><b></b></span>
                                <span class="device-phone-connection {{ $user->status === 'active' ? 'is-connected' : 'is-disconnected' }}">
                                    <i></i>{{ $user->status === 'active' ? 'Connected' : 'Offline' }}
                                </span>
                                <span class="device-phone-home"></span>
                            </div>
                        </div>
                        <div class="device-card-content">
                        <div class="device-specs">
                            <div class="device-spec">
                                <span class="device-spec-icon device-tone-green"><x-icon name="device" /></span>
                                <span class="device-spec-copy"><small>Device Name</small><strong>Eemo Tracker</strong></span>
                            </div>
                            <div class="device-spec">
                                <span class="device-spec-icon device-tone-red"><x-icon name="cpu" /></span>
                                <span class="device-spec-copy"><small>Model</small><strong>GT06</strong></span>
                            </div>
                            <div class="device-spec">
                                <span class="device-spec-icon device-tone-orange"><x-icon name="signal" /></span>
                                <span class="device-spec-copy"><small>Network</small><strong>Airtel 4G <span class="device-signal-bars" aria-label="Strong signal"><i></i><i></i><i></i></span></strong></span>
                            </div>
                            <div class="device-spec">
                                <span class="device-spec-icon device-tone-blue"><x-icon name="map-pin" /></span>
                                <span class="device-spec-copy"><small>GPS Status</small><strong><span class="device-gps-status {{ $user->status === 'active' ? 'is-on' : 'is-off' }}"><i></i>{{ $user->status === 'active' ? 'ON' : 'OFF' }}</span></strong></span>
                            </div>
                            <div class="device-spec">
                                <span class="device-spec-icon device-tone-teal"><x-icon name="battery" /></span>
                                <span class="device-spec-copy device-battery-copy"><small>Battery</small><strong>78%</strong><span class="device-battery-meter" role="meter" aria-label="Battery level" aria-valuemin="0" aria-valuemax="100" aria-valuenow="78"><i></i></span></span>
                            </div>
                            <div class="device-spec">
                                <span class="device-spec-icon device-tone-purple"><x-icon name="clock" /></span>
                                <span class="device-spec-copy"><small>Last Update</small><strong>{{ now()->format('d M Y, h:i A') }}</strong></span>
                            </div>
                        </div>
                    @else
                        <div class="registered-user-details">
                            <div class="registered-user-detail">
                                <span class="detail-icon" aria-hidden="true">✉</span>
                                <span>{{ $user->email }}</span>
                            </div>
                            <div class="registered-user-detail">
                                <span class="detail-icon" aria-hidden="true">☎</span>
                                <span>{{ $user->phone }}</span>
                            </div>
                            @if ($user->address)
                                <div class="registered-user-detail address-detail">
                                    <span class="detail-icon" aria-hidden="true">⌖</span>
                                    <span>{{ $user->address }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="registered-user-actions">
                        @if ($isDevicePage)
                            <a class="action-button track-button" href="{{ route('admin.tracking.live-location') }}">Track</a>
                            <a class="action-button report-button" href="{{ route('admin.user-management.reports') }}">Report</a>
                        @else
                            <button type="button" class="action-button edit-button"
                                data-user-id="{{ $user->id }}"
                                data-user-name="{{ $user->name }}"
                                data-user-email="{{ $user->email }}"
                                data-user-phone="{{ $user->phone }}"
                                data-user-address="{{ $user->address ?? '' }}"
                                data-user-status="{{ $user->status }}"
                                data-user-image="{{ $user->profile_image ? Storage::disk('public')->url($user->profile_image) : '' }}">Edit</button>
                            <button type="button" class="action-button view-button"
                                data-user-id="{{ $user->id }}"
                                data-user-name="{{ $user->name }}"
                                data-user-email="{{ $user->email }}"
                                data-user-phone="{{ $user->phone }}"
                                data-user-address="{{ $user->address ?? '' }}"
                                data-user-status="{{ $user->status }}"
                                data-user-code="{{ $user->user_code }}"
                                data-user-last-login="{{ $user->last_login_at ? $user->last_login_at->format('d M Y, h:i A') : 'Never' }}"
                                data-user-image="{{ $user->profile_image ? Storage::disk('public')->url($user->profile_image) : '' }}">View</button>
                        @endif
                    </div>
                    @if ($isDevicePage)
                        </div>
                    @endif
                </article>
            @empty
                <div class="user-grid-empty">
                    <span class="empty-icon" aria-hidden="true">+</span>
                    <strong>No {{ $recordPlural }} found</strong>
                    <p>{{ $search ? 'Try another search, or add a new '.$recordLabel.'.' : 'Add your first '.$recordLabel.' to get started.' }}</p>
                </div>
            @endforelse
        </div>

        @if ($users->hasPages())
            <div class="pagination-wrap">
                {{ $users->links() }}
            </div>
        @endif
    </section>

    <div class="user-modal-backdrop" id="user-modal-backdrop" data-entity="{{ $recordLabel }}" @if ($errors->any()) data-reopen-create @else hidden @endif>
        <div class="user-modal" role="dialog" aria-modal="true" aria-labelledby="user-modal-title">
            <div class="user-modal-header">
                <div>
                    <p class="eyebrow" id="user-modal-tag">CREATE {{ strtoupper($recordLabel) }}</p>
                    <h3 id="user-modal-title">Register a new {{ $recordLabel }}</h3>
                    <p class="user-modal-subtitle" id="user-modal-subtitle">Add a {{ $recordLabel }} record to your registry.</p>
                </div>
                <button type="button" class="close-modal" data-close-user-modal aria-label="Close {{ $recordLabel }} modal">×</button>
            </div>

            <form id="user-form" method="POST" action="{{ $isDevicePage ? '#' : route('admin.user-management.users.store') }}" data-demo-only="{{ $isDevicePage ? 'true' : 'false' }}" data-create-action="{{ $isDevicePage ? '#' : route('admin.user-management.users.store') }}" data-update-action-template="{{ $isDevicePage ? '' : route('admin.user-management.users.update', ['user' => '__USER__']) }}" enctype="multipart/form-data" class="user-form">
                @csrf
                <input type="hidden" name="_method" id="user-form-method" value="POST">
                @if ($errors->any())
                    <div class="user-form-errors" role="alert">
                        <strong>Please check the following fields:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="form-grid">
                    <label class="user-field full-width">
                        <span>Profile image <small>(optional)</small></span>
                        <span class="user-image-picker">
                            <span class="image-picker-icon" aria-hidden="true">↑</span>
                            <span class="image-picker-copy"><strong id="user-image-name">Choose a profile photo</strong><small>JPG, PNG or WEBP · Max 2 MB</small></span>
                            <input id="user-image" type="file" name="profile_image" accept="image/jpeg,image/png,image/webp" @class(['is-invalid' => $errors->has('profile_image')]) @if ($errors->has('profile_image')) aria-describedby="user-image-error" @endif>
                            <img id="user-image-preview" alt="Selected profile preview" hidden>
                        </span>
                        @error('profile_image')<small class="user-field-error" id="user-image-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="user-field">
                        <span>Name <b>*</b></span>
                        <input id="user-name" type="text" name="name" value="{{ old('name') }}" placeholder="Enter full name" autocomplete="name" maxlength="255" required @class(['is-invalid' => $errors->has('name')]) @if ($errors->has('name')) aria-describedby="user-name-error" @endif>
                        @error('name')<small class="user-field-error" id="user-name-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="user-field">
                        <span>Email <b>*</b></span>
                        <input id="user-email" type="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" autocomplete="email" maxlength="255" required @class(['is-invalid' => $errors->has('email')]) @if ($errors->has('email')) aria-describedby="user-email-error" @endif>
                        @error('email')<small class="user-field-error" id="user-email-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="user-field">
                        <span>Number <b>*</b></span>
                        <input id="user-phone" type="tel" name="phone" value="{{ old('phone') }}" placeholder="9876543210" autocomplete="tel" maxlength="20" required @class(['is-invalid' => $errors->has('phone')]) @if ($errors->has('phone')) aria-describedby="user-phone-error" @endif>
                        @error('phone')<small class="user-field-error" id="user-phone-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="user-field">
                        <span>Password <b id="password-required-mark">*</b></span>
                        <input id="user-password" type="password" name="password" placeholder="Enter password" autocomplete="new-password" required @class(['is-invalid' => $errors->has('password')]) @if ($errors->has('password')) aria-describedby="user-password-error" @endif>
                        <small class="user-field-hint" id="password-hint">At least 8 characters. Password is stored securely.</small>
                        @error('password')<small class="user-field-error" id="user-password-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="user-field full-width">
                        <span>Address <small>(optional)</small></span>
                        <textarea id="user-address" name="address" rows="3" placeholder="Enter address" maxlength="500" @class(['is-invalid' => $errors->has('address')]) @if ($errors->has('address')) aria-describedby="user-address-error" @endif>{{ old('address') }}</textarea>
                        @error('address')<small class="user-field-error" id="user-address-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="user-field" id="user-status-field" hidden>
                        <span>Status <b>*</b></span>
                        <select id="user-status" name="status" disabled>
                            <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                        </select>
                        @error('status')<small class="user-field-error" id="user-status-error">{{ $message }}</small>@enderror
                    </label>
                </div>

                @if ($isDevicePage)
                    <p class="user-field-hint">Demo only — device changes are not saved.</p>
                @endif
                <div class="modal-actions">
                    <button type="button" class="secondary-button" data-close-user-modal id="user-cancel-button">Cancel</button>
                    <button type="submit" class="primary-button" id="user-submit-button"><span id="user-submit-label">Create {{ $recordLabel }}</span></button>
                </div>
            </form>
            <div class="user-view-details" id="user-view-details" hidden>
                <div class="user-view-profile">
                    <img id="view-user-image" class="user-avatar" alt="">
                    <span id="view-user-initials" class="user-avatar initials" hidden></span>
                    <div>
                        <h4 id="view-user-name"></h4>
                        <span id="view-user-code" class="code-pill"></span>
                    </div>
                </div>
                <dl class="user-view-list">
                    <div><dt>Email</dt><dd id="view-user-email"></dd></div>
                    <div><dt>Phone</dt><dd id="view-user-phone"></dd></div>
                    <div><dt>Address</dt><dd id="view-user-address"></dd></div>
                    <div><dt>Status</dt><dd id="view-user-status"></dd></div>
                    <div><dt>Last login</dt><dd id="view-user-last-login"></dd></div>
                </dl>
                <div class="modal-actions user-view-actions">
                    <button type="button" class="secondary-button" data-close-user-modal>Close</button>
                    <button type="button" class="primary-button" id="view-edit-user">Edit {{ $recordLabel }}</button>
                </div>
            </div>
        </div>
    </div>

@endsection
