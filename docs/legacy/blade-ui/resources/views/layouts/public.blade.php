<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="@yield('meta_description', 'InternMatch — an intelligent student internship placement and monitoring system for the University of Mindanao Tagum College.')">
  <title>@yield('title', 'InternMatch') · UM Tagum College</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('css/internmatch.css') }}" rel="stylesheet">
  @stack('styles')
</head>
<body>
  <a href="#main" class="visually-hidden-focusable position-absolute top-0 start-0 m-2 btn btn-brand btn-sm">Skip to content</a>
  @include('partials.public-nav')
  <main id="main">@yield('content')</main>
  @include('partials.public-footer')
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('js/internmatch.js') }}"></script>
  @stack('scripts')
</body>
</html>
