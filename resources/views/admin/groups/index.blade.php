@extends('layouts.admin')
@section('title', 'Groups')
@section('content')
<section class="user-management group-management">
    <header class="user-panel-header">
        <div class="user-stats">
            <article class="user-stat total-stat"><span>Total groups</span><strong>{{ $totalCount }}</strong></article>
            <article class="user-stat active-stat"><span>Active</span><strong>{{ $activeCount }}</strong></article>
            <article class="user-stat inactive-stat"><span>Inactive / blocked</span><strong>{{ $inactiveCount + $blockedCount }}</strong></article>
        </div>
        <div class="user-controls">
            <button type="button" class="primary-button" data-open-group-create @disabled($availableCreators->isEmpty())>
                <x-icon name="users" /> Create group
            </button>
        </div>
    </header>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($availableCreators->isEmpty())
        <div class="alert alert-info" role="status">
            @if ($users->isEmpty())
                Add a user before creating a group.
            @else
                Every user already has a group. A user can create only one group.
            @endif
        </div>
    @endif

    <x-card class="group-list-card">
        <div class="table-heading">
            <h2>Groups</h2>
            <span class="muted">{{ $groups->total() }} {{ Str::plural('group', $groups->total()) }}</span>
        </div>
        @if ($groups->isEmpty())
            <div class="empty-state">
                <span class="tile-icon"><x-icon name="users" /></span>
                <h3>No groups yet</h3>
                <p>Create a group and add its members to get started.</p>
            </div>
        @else
            <div class="group-table-wrap">
                <table class="group-table">
                    <thead>
                        <tr>
                            <th scope="col">Group ID</th>
                            <th scope="col">Created by</th>
                            <th scope="col">Group members</th>
                            <th scope="col">Status</th>
                            <th scope="col">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groups as $group)
                            <tr>
                                <td><span class="group-id">#{{ $group->id }}</span></td>
                                <td>
                                    <strong>{{ $group->creator->name }}</strong>
                                    <small>{{ $group->creator->user_code }}</small>
                                </td>
                                <td>
                                    <div class="group-member-list">
                                        @foreach ($group->member_users as $member)
                                            <span>{{ $member->name }}</span>
                                        @endforeach
                                    </div>
                                    <small>{{ count($group->group_members) }} {{ Str::plural('member', count($group->group_members)) }}</small>
                                </td>
                                <td><span class="group-status is-{{ $group->status }}">{{ ucfirst($group->status) }}</span></td>
                                <td>{{ $group->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $groups->links() }}
        @endif
    </x-card>

    <dialog class="support-dialog group-create-dialog {{ $errors->any() ? 'is-reopen' : '' }}" id="group-create-dialog" aria-labelledby="group-create-title">
        <div class="user-modal-header group-modal-header">
            <div>
                <p class="eyebrow">GROUP MANAGEMENT</p>
                <h3 id="group-create-title">Create a new group</h3>
                <p class="user-modal-subtitle">Choose the group owner, add members, and set a status.</p>
            </div>
            <button type="button" class="close-modal" data-close-support-dialog aria-label="Close create group">×</button>
        </div>
        <form method="POST" action="{{ route('admin.groups.store') }}" class="group-create-form" id="group-create-form">
            @csrf
            <div class="group-form-grid">
                <div class="user-field group-creator-field">
                    <label for="group-creator-trigger">Created by <b>*</b></label>
                    <div class="group-select-picker" id="group-creator-picker">
                        <button type="button" class="group-picker-trigger" id="group-creator-trigger" aria-expanded="false" aria-controls="group-creator-dropdown">
                            <span class="group-picker-current" id="group-creator-current">Select a group owner</span>
                            <x-icon name="chevron" class="group-picker-chevron" />
                        </button>
                        <input type="hidden" name="created_by" id="created_by" value="{{ old('created_by') }}">
                        <div class="group-picker-dropdown" id="group-creator-dropdown" hidden>
                            <div class="group-member-search-wrap">
                                <x-icon name="search" />
                                <input id="group-creator-search" type="search" placeholder="Search by name or user ID" autocomplete="off" aria-controls="group-creator-options">
                            </div>
                            <div class="group-picker-options" id="group-creator-options" role="group" aria-label="Group owners">
                                @foreach ($availableCreators as $user)
                                    <button type="button" class="group-picker-option" data-creator-id="{{ $user->id }}" data-creator-name="{{ $user->name }}" data-creator-code="{{ $user->user_code }}" data-creator-search="{{ mb_strtolower($user->name.' '.$user->user_code) }}">
                                        <span class="group-member-checkmark" aria-hidden="true"><x-icon name="check" /></span>
                                        <span class="group-member-option-copy"><strong>{{ $user->name }}</strong><small>{{ $user->user_code }}</small></span>
                                        <span class="group-member-selected-mark">Select</span>
                                    </button>
                                @endforeach
                                <p class="group-member-no-results" id="group-creator-no-results" hidden>No users match your search.</p>
                            </div>
                        </div>
                    </div>
                    @error('created_by')<small class="user-field-error">{{ $message }}</small>@enderror
                    <small class="group-field-hint">Each user can create only one group.</small>
                </div>

                <label class="user-field" for="status">
                    <span>Group status <b>*</b></span>
                    <select id="status" name="status" required>
                        @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'blocked' => 'Blocked'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<small class="user-field-error">{{ $message }}</small>@enderror
                </label>

                <div class="user-field full-width group-member-field">
                    <label for="group-member-trigger">Group members <b>*</b></label>
                    <div class="group-member-picker" id="group-member-picker">
                        <div class="group-member-control" id="group-member-control">
                            <button type="button" class="group-picker-trigger group-member-trigger" id="group-member-trigger" aria-expanded="false" aria-controls="group-member-dropdown">
                                <span class="group-picker-current" id="group-member-placeholder">Click to select group members</span>
                                <span class="group-member-count" id="group-member-count" aria-live="polite">0 selected</span>
                                <x-icon name="chevron" class="group-picker-chevron" />
                            </button>
                            <div class="group-picker-dropdown group-member-dropdown" id="group-member-dropdown" hidden>
                                <div class="group-member-search-wrap">
                                    <x-icon name="search" />
                                    <input id="group-member-search" type="search" placeholder="Search by name or user ID" autocomplete="off" aria-controls="group-member-options">
                                </div>
                                <div class="group-member-options" id="group-member-options" role="group" aria-label="Group members">
                                    @foreach ($users as $user)
                                        <label class="group-member-option" data-member-search="{{ mb_strtolower($user->name.' '.$user->user_code) }}">
                                            <input type="checkbox" name="group_members[]" value="{{ $user->id }}" @checked(in_array((string) $user->id, array_map('strval', is_array(old('group_members')) ? old('group_members') : []), true))>
                                            <span class="group-member-checkmark" aria-hidden="true"><x-icon name="check" /></span>
                                            <span class="group-member-option-copy">
                                                <strong>{{ $user->name }}</strong>
                                                <small>{{ $user->user_code }}</small>
                                            </span>
                                            <span class="group-member-selected-mark">Selected</span>
                                        </label>
                                    @endforeach
                                    <p class="group-member-no-results" id="group-member-no-results" hidden>No users match your search.</p>
                                </div>
                            </div>
                        </div>
                        <div class="group-selected-members" id="group-selected-members" aria-label="Selected group members"></div>
                    </div>
                    @error('group_members')<small class="user-field-error">{{ $message }}</small>@enderror
                    @error('group_members.*')<small class="user-field-error">{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="group-modal-actions">
                <button type="button" class="secondary-button" data-close-support-dialog>Cancel</button>
                <button type="submit" class="primary-button"><x-icon name="users" /> Create group</button>
            </div>
        </form>
    </dialog>
</section>
@endsection
