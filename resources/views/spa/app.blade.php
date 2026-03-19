<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $appName }}</title>
    @vite(['resources/js/app.js'])
</head>
<body>
    <noscript>To run this application, JavaScript is required to be enabled.</noscript>
    <div id="app"></div>
</body>
</html>
