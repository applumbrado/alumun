<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'AluMun') }}</title>

    @routes
    @vite(['resources/css/app.css','resources/js/app.js'])
    @inertiaHead
    <script type="text/javascript">
        window.Laravel = {
            csrfToken: "{{ csrf_token() }}",
            jsPermissions: {!! auth()->check()?auth()->user()->jsPermissions():0 !!}
        }
    </script>

</head>
<body class="font-sans antialiased bg-slate-950 text-white">
@inertia
</body>
</html>
