@if(file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
@if(!empty($adminTheme))
@vite(['resources/css/app.css', 'resources/css/admin-theme.css', 'resources/js/app.js'])
@else
@vite(['resources/css/app.css', 'resources/js/app.js'])
@endif
@else
<link rel="stylesheet" href="{{ asset('assets/app.css') }}?v={{ hash_file('sha256', public_path('assets/app.css')) }}">
@if(!empty($adminTheme))
<link rel="stylesheet" href="{{ asset('assets/admin-theme.css') }}?v={{ hash_file('sha256', public_path('assets/admin-theme.css')) }}">
@endif
<script src="{{ asset('assets/app.js') }}?v={{ hash_file('sha256', public_path('assets/app.js')) }}" defer></script>
@endif
