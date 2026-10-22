@extends('layouts.admin')
@section('title', 'Support')
@section('content')
<section class="user-management support-management">
    <header class="user-panel-header support-panel-header">
        <div class="user-stats support-stats">
            <article class="user-stat total-stat"><span>Total tickets</span><strong>{{ $totalCount }}</strong></article>
            <article class="user-stat active-stat"><span>Resolved</span><strong>{{ $resolvedCount }}</strong></article>
            <article class="user-stat inactive-stat"><span>Pending</span><strong>{{ $pendingCount }}</strong></article>
        </div>
        <div class="user-controls">
            <form method="GET" action="{{ route('admin.support') }}" class="user-search-form">
                <span class="search-icon" aria-hidden="true">⌕</span>
                <input id="support-search" type="search" name="q" value="{{ $search }}" placeholder="Search tickets..." aria-label="Search support tickets">
                <button type="submit" class="search-button">Search</button>
            </form>
            <button type="button" class="primary-button" data-open-support-create>
                <x-icon name="user" /> Create ticket
            </button>
        </div>
    </header>

@if (session('status'))
    <div class="alert alert-success" role="status">{{ session('status') }}</div>
@endif

<x-card class="support-ticket-list">
    <div class="table-heading">
        <h2>Support tickets</h2>
        <span class="muted">{{ $tickets->total() }} {{ Str::plural('ticket', $tickets->total()) }}</span>
    </div>
    @if ($tickets->isEmpty())
        <div class="empty-state">
            <span class="tile-icon"><x-icon name="headset" /></span>
            <h3>{{ $search ? 'No matching tickets' : 'No support tickets yet' }}</h3>
            <p>{{ $search ? 'Try a different search.' : 'Create a ticket to get started.' }}</p>
        </div>
    @else
        <div class="support-ticket-listing">
            @foreach ($tickets as $ticket)
                <article class="support-ticket-row">
                    <div class="support-ticket-primary">
                        <span class="support-ticket-id">#{{ $ticket->id }}</span>
                        <div class="support-ticket-customer">
                            <strong>{{ $ticket->name }}</strong>
                            <span>{{ $ticket->number }}</span>
                            <span>Created by: {{ $ticket->createdBy?->name ?? 'Not linked' }}</span>
                        </div>
                    </div>
                    <p class="support-ticket-summary">{{ $ticket->issue }}</p>
                    <span class="event-badge {{ $ticket->status === 'rejected' ? 'warning' : '' }}">{{ ucfirst($ticket->status) }}</span>
                    <button type="button" class="btn btn-secondary support-ticket-open" data-open-support-ticket="support-ticket-{{ $ticket->id }}">
                        {{ $ticket->status === 'pending' ? 'Resolve' : 'View details' }}
                    </button>
                </article>

                <dialog class="support-dialog {{ request('ticket') == $ticket->id && $errors->any() ? 'is-reopen' : '' }}" id="support-ticket-{{ $ticket->id }}" aria-labelledby="support-ticket-title-{{ $ticket->id }}">
                    <div class="support-dialog-header">
                        <div><span class="eyebrow">TICKET #{{ $ticket->id }}</span><h2 id="support-ticket-title-{{ $ticket->id }}">Ticket details</h2></div>
                        <button type="button" class="close-modal" data-close-support-dialog aria-label="Close ticket details">×</button>
                    </div>
                    <div class="support-dialog-details">
                        <div><span>Name</span><strong>{{ $ticket->name }}</strong></div>
                        <div><span>Number</span><strong>{{ $ticket->number }}</strong></div>
                        <div><span>Created by</span><strong>{{ $ticket->createdBy?->name ?? 'Not linked' }}</strong></div>
                        <div class="support-dialog-issue"><span>Issue</span><p>{{ $ticket->issue }}</p></div>
                        <div class="support-dialog-created"><span>Created</span><strong>{{ $ticket->created_at->format('d M Y, h:i A') }}</strong></div>
                    </div>
                    <form method="POST" action="{{ route('admin.support.update', $ticket) }}?ticket={{ $ticket->id }}" class="support-update-form">
                        @csrf
                        @method('PATCH')
                        <label class="field">
                            <span>Status</span>
                            <select name="status" class="input" required>
                                <option value="pending" @selected((request('ticket') == $ticket->id ? old('status', $ticket->status) : $ticket->status) === 'pending')>Pending</option>
                                <option value="resolved" @selected((request('ticket') == $ticket->id ? old('status', $ticket->status) : $ticket->status) === 'resolved')>Resolved</option>
                                <option value="rejected" @selected((request('ticket') == $ticket->id ? old('status', $ticket->status) : $ticket->status) === 'rejected')>Rejected</option>
                            </select>
                            @if (request('ticket') == $ticket->id)
                                @error('status')<small class="field-error">{{ $message }}</small>@enderror
                            @endif
                        </label>
                        <label class="field">
                            <span>Remark</span>
                            <textarea name="remark" class="input" rows="3" maxlength="2000" placeholder="Add a remark (optional)">{{ request('ticket') == $ticket->id ? old('remark', $ticket->remark) : $ticket->remark }}</textarea>
                            @if (request('ticket') == $ticket->id)
                                @error('remark')<small class="field-error">{{ $message }}</small>@enderror
                            @endif
                        </label>
                        <div class="support-dialog-actions">
                            <button type="button" class="btn btn-secondary" data-close-support-dialog>Cancel</button>
                            <button type="submit" class="btn btn-primary">Save status and remark</button>
                        </div>
                    </form>
                </dialog>
            @endforeach
        </div>
        {{ $tickets->links() }}
    @endif
</x-card>

<dialog class="support-dialog support-create-dialog {{ $errors->has('name') || $errors->has('number') || $errors->has('issue') || $errors->has('created_by_user_id') ? 'is-reopen' : '' }}" id="support-create-dialog" aria-labelledby="support-create-title">
    <div class="support-dialog-header">
        <div><span class="eyebrow">CUSTOMER CARE</span><h2 id="support-create-title">Create support ticket</h2></div>
        <button type="button" class="close-modal" data-close-support-dialog aria-label="Close create ticket">×</button>
    </div>
    <p class="support-dialog-description">Enter the customer's contact details and describe the issue.</p>
    <form method="POST" action="{{ route('admin.support.store') }}" class="support-create-form">
        @csrf
        <x-field name="name" label="Name" :value="old('name')" maxlength="255" required />
        <x-field name="number" label="Number" :value="old('number')" type="tel" maxlength="30" required />
        <label class="field" for="created_by_user_id">
            <span>Created by <small>(optional)</small></span>
            <select id="created_by_user_id" name="created_by_user_id" class="input">
                <option value="">Not linked</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((string) old('created_by_user_id') === (string) $user->id)>{{ $user->name }} · {{ $user->user_code }}</option>
                @endforeach
            </select>
            @error('created_by_user_id')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <div class="field support-issue-field">
            <label for="issue">Issue</label>
            <textarea id="issue" name="issue" class="input" rows="4" maxlength="5000" required>{{ old('issue') }}</textarea>
            @error('issue')<small class="field-error" id="issue-help">{{ $message }}</small>@enderror
        </div>
        @error('name')<small class="field-error">{{ $message }}</small>@enderror
        @error('number')<small class="field-error">{{ $message }}</small>@enderror
        <div class="support-dialog-actions">
            <button type="button" class="btn btn-secondary" data-close-support-dialog>Cancel</button>
            <button type="submit" class="btn btn-primary">Create ticket</button>
        </div>
    </form>
</dialog>
</section>
@endsection
