<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Q One')</title>
  <meta name="robots" content="@yield('robots', 'index,follow')">
  <meta name="theme-color" content="#0B1220">
  <link rel="icon" href="{{ asset('img/logo.svg') }}" type="image/svg+xml">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  @php $primaryColor = \App\Models\Setting::safe('primary_color', '#6D3DF5') ?: '#6D3DF5'; @endphp
  <style>:root{--primary: {{ $primaryColor }}}</style>
  @stack('head')
</head>
<body class="@yield('body_class')">
@yield('content')
<script src="{{ asset('js/app.js') }}" defer></script>
@stack('scripts')
</body>
</html>
