<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'QEAI One')</title>
  <meta name="robots" content="@yield('robots', 'index,follow')">
  <meta name="theme-color" content="#11132B">
  <link rel="icon" href="{{ asset('img/logo.svg') }}" type="image/svg+xml">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  @php $primaryColor = \App\Models\Setting::safe('primary_color', '#6D3DF5') ?: '#6D3DF5'; @endphp
  <style>:root{--primary: {{ $primaryColor }}}</style>
</head>
<body class="@yield('body_class')">
@yield('content')
</body>
</html>
