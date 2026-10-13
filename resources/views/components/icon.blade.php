@props(['name' => 'grid'])
@php
$paths = [
'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
'shield' => '<path d="M12 3 3 7v5c0 5 9 10 9 10s9-5 9-10V7l-9-4Z"/><path d="m8 12 3 3 5-6"/>',
'activity' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
'device' => '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4"/>',
'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
'logout' => '<path d="M10 4H4v16h6m4-12 4 4-4 4m-6-4h10"/>',
'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
'chevron' => '<path d="m6 9 6 6 6-6"/>',
'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
'upload' => '<path d="M12 16V3m-5 5 5-5 5 5M3 16v5h18v-5"/>',
'check' => '<path d="m5 12 4 4L19 6"/>',
'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
'waves' => '<path d="M2 8c2.5 0 2.5 2 5 2s2.5-2 5-2 2.5 2 5 2 2.5-2 5-2M2 14c2.5 0 2.5 2 5 2s2.5-2 5-2 2.5 2 5 2 2.5-2 5-2M2 20c2.5 0 2.5 2 5 2s2.5-2 5-2 2.5 2 5 2 2.5-2 5-2"/>',
'train' => '<rect x="5" y="2" width="14" height="17" rx="3"/><path d="M8 22l2-3m6 3-2-3M8 14h8M8 6h8"/><circle cx="8.5" cy="17" r="1"/><circle cx="15.5" cy="17" r="1"/>',
'plane' => '<path d="m2 16 8-4V3a2 2 0 0 1 4 0v9l8 4v2l-8-2v4l2 2v1l-4-1-4 1v-1l2-2v-4l-8 2z"/>',
'box' => '<path d="m12 2 9 4.5v11L12 22l-9-4.5v-11z"/><path d="m3.3 6.7 8.7 4.5 8.7-4.5M12 22V11.2M7.5 4.3l9 4.6"/>',
'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
'brand' => '<path d="m12 2 10 6v8l-10 6-10-6V8l10-6Z"/><path d="m7 9 5 3 5-3m-5 3v6"/>',
];
@endphp
<svg {{ $attributes->class(['icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['grid'] !!}</svg>
