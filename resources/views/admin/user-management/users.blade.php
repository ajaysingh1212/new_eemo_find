@extends('layouts.admin')

@section('title', 'User Management')

@section('content')
    @php
        $totalUserCount = $totalUsers ?? $users->total();
    @endphp

    <section class="user-management">
        <div class="user-panel-header">
            <div class="user-stats">
                <article class="user-stat total-stat">
                    <span>Total users</span>
                    <strong>{{ $totalUserCount }}</strong>
                </article>
                <article class="user-stat active-stat">
                    <span>Active users</span>
                    <strong>{{ $activeUsers }}</strong>
                </article>
                <article class="user-stat inactive-stat">
                    <span>Inactive users</span>
                    <strong>{{ $inactiveUsers }}</strong>
                </article>
            </div>

            <div class="user-controls">
                <form method="GET" action="{{ route('admin.user-management.users') }}" class="user-search-form">
                    <span class="search-icon" aria-hidden="true">⌕</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search users..." aria-label="Search users">
                    <button type="submit" class="search-button">Search</button>
                </form>
                <button type="button" class="primary-button" data-open-user-modal>
                    <x-icon name="user" /> Add user
                </button>
            </div>
        </div>

        <div class="user-card-grid">
            @forelse ($users as $user)
                <article class="registered-user-card">
                    <div class="registered-user-top">
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

                    <div class="registered-user-actions">
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
                    </div>
                </article>
            @empty
                <div class="user-grid-empty">
                    <span class="empty-icon" aria-hidden="true">+</span>
                    <strong>No users found</strong>
                    <p>{{ $search ? 'Try another search, or add a new user.' : 'Add your first user to get started.' }}</p>
                </div>
            @endforelse
        </div>

        @if ($users->hasPages())
            <div class="pagination-wrap">
                {{ $users->links() }}
            </div>
        @endif
    </section>

    <div class="user-modal-backdrop" id="user-modal-backdrop" @if ($errors->any()) data-reopen-create @else hidden @endif>
        <div class="user-modal" role="dialog" aria-modal="true" aria-labelledby="user-modal-title">
            <div class="user-modal-header">
                <div>
                    <p class="eyebrow" id="user-modal-tag">CREATE USER</p>
                    <h3 id="user-modal-title">Register a new user</h3>
                    <p class="user-modal-subtitle" id="user-modal-subtitle">Add a user record to your registry.</p>
                </div>
                <button type="button" class="close-modal" data-close-user-modal aria-label="Close user modal">×</button>
            </div>

            <form id="user-form" method="POST" action="{{ route('admin.user-management.users.store') }}" data-create-action="{{ route('admin.user-management.users.store') }}" data-update-action-template="{{ route('admin.user-management.users.update', ['user' => '__USER__']) }}" enctype="multipart/form-data" class="user-form">
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

                <div class="modal-actions">
                    <button type="button" class="secondary-button" data-close-user-modal id="user-cancel-button">Cancel</button>
                    <button type="submit" class="primary-button" id="user-submit-button"><span id="user-submit-label">Create user</span></button>
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
                    <button type="button" class="primary-button" id="view-edit-user">Edit user</button>
                </div>
            </div>
        </div>
    </div>

@endsection
