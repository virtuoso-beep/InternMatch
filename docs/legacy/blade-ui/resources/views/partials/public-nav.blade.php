@php $inner = $inner ?? false; @endphp
<nav class="pub-nav {{ $inner ? 'inner solid' : '' }}" aria-label="Main navigation">
  <div class="container">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('landing') }}" class="brand d-flex align-items-center gap-2 text-decoration-none">
        <span class="sb-mark">IM</span>
        <span><strong>InternMatch</strong><span>University of Mindanao Tagum College</span></span>
      </a>

      <ul class="nav d-none d-xl-flex ms-auto align-items-center">
        <li><a class="nav-link" href="{{ route('landing') }}">Home</a></li>
        <li><a class="nav-link" href="{{ route('public.about') }}">About</a></li>
        <li><a class="nav-link" href="{{ route('public.features') }}">Features</a></li>
        <li><a class="nav-link" href="{{ route('public.how-it-works') }}">How It Works</a></li>
        <li><a class="nav-link" href="{{ route('landing') }}#partners">Partners</a></li>
        <li><a class="nav-link" href="{{ route('public.faq') }}">FAQ</a></li>
        <li><a class="nav-link" href="{{ route('public.contact') }}">Contact</a></li>
      </ul>

      <div class="d-none d-lg-flex align-items-center gap-2 ms-auto ms-xl-3">
        <a href="{{ route('login') }}" class="btn btn-light-outline btn-sm">Login</a>
        <a href="{{ route('login') }}" class="btn btn-brand btn-sm">Get Started</a>
      </div>

      <button class="btn btn-light-outline btn-sm d-xl-none ms-auto" type="button" data-bs-toggle="offcanvas" data-bs-target="#pubMenu" aria-label="Open menu">
        <i class="bi bi-list"></i>
      </button>
    </div>
  </div>
</nav>

<div class="offcanvas offcanvas-end" tabindex="-1" id="pubMenu" aria-label="Mobile menu">
  <div class="offcanvas-header border-bottom">
    <span class="d-flex align-items-center gap-2"><span class="sb-mark">IM</span><strong style="font-family:Poppins">InternMatch</strong></span>
    <button class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <ul class="list-unstyled d-grid gap-1">
      @foreach([['Home','landing'],['About','public.about'],['Features','public.features'],['How It Works','public.how-it-works'],['FAQ','public.faq'],['Contact','public.contact'],['Help Center','public.help']] as $l)
        <li><a class="d-block py-2 text-body-2 fw-medium" href="{{ route($l[1]) }}">{{ $l[0] }}</a></li>
      @endforeach
    </ul>
    <div class="d-grid gap-2 mt-3">
      <a href="{{ route('login') }}" class="btn btn-ghost">Login</a>
      <a href="{{ route('login') }}" class="btn btn-brand">Get Started</a>
    </div>
  </div>
</div>
