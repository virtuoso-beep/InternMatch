{{--
  Authenticated application shell.
  Expected variables (pass from your controller or @section):
    $role      e.g. 'student' | 'admin' | 'coordinator' | 'supervisor' | 'chair' | 'dean'
    $active    current nav slug, e.g. 'dashboard'
--}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Dashboard') · InternMatch</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('css/internmatch.css') }}" rel="stylesheet">
  @stack('styles')
</head>
<body class="app-shell">
  <a href="#main" class="visually-hidden-focusable position-absolute top-0 start-0 m-2 btn btn-brand btn-sm">Skip to content</a>
  @include('partials.sidebar', ['role' => $role ?? 'student', 'active' => $active ?? 'dashboard'])
  <div class="sidebar-backdrop d-lg-none"></div>
  <div class="main-wrap">
    @include('partials.topbar', ['role' => $role ?? 'student'])
    <main id="main" class="page-body">
      @includeWhen(isset($flash), 'components.alert', ['type' => 'success', 'message' => $flash ?? ''])
      @yield('content')
    </main>
    <footer class="app-footer d-flex flex-wrap justify-content-between gap-2">
      <span>&copy; {{ date('Y') }} University of Mindanao Tagum College — InternMatch</span>
      <span class="d-flex gap-3">
        <a href="{{ route('public.privacy') }}">Privacy</a>
        <a href="{{ route('public.terms') }}">Terms</a>
        <a href="{{ route('public.accessibility') }}">Accessibility</a>
        <span>v1.0.0</span>
      </span>
    </footer>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('js/internmatch.js') }}"></script>
  @stack('scripts')
</body>
</html>
