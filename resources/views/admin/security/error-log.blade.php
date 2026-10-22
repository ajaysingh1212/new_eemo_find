@extends('layouts.admin')
@section('title', 'Error Log')
@section('content')
<x-card class="log-card">
    <div class="table-heading">
        <h2>Laravel application log</h2>
        <div class="filter-actions">
            <span class="muted">{{ $lineCount }} {{ Str::plural('line', $lineCount) }}</span>
            <a class="btn btn-secondary" href="{{ route('admin.developer.error-log') }}">Refresh</a>
            @if ($canClear)
                <form method="POST" action="{{ route('admin.developer.error-log.destroy') }}" data-confirm="Clear all entries from the current Laravel log file? This cannot be undone.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Clear log</button>
                </form>
            @endif
        </div>
    </div>
    @if ($message)
        <div class="empty-state">
            <span class="tile-icon"><x-icon name="activity" /></span>
            <h3>No log entries to show</h3>
            <p>{{ $message }}</p>
        </div>
    @else
        <pre class="error-log-output" aria-label="Recent Laravel log entries">{{ implode("\n", $lines) }}</pre>
    @endif
</x-card>
<div class="workspace-note"><x-icon name="shield" /><span>Showing the latest log entries only. Passwords, tokens, and authorization credentials are redacted.</span></div>
@endsection
