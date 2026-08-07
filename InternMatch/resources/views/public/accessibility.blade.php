@extends('layouts.public')
@section('title','Accessibility Statement')
@section('meta_description','Our commitment to WCAG 2.1 AA conformance across the InternMatch platform.')
@section('content')
<header class="pt-5" style="background:var(--warm-gray);border-bottom:1px solid var(--border)">
  <div class="container" style="padding:6.5rem 0 3.5rem">
    <nav class="fs-12 text-muted-2 mb-2" aria-label="Breadcrumb"><a href="{{ route('landing') }}">Home</a> <span class="mx-1">/</span> Accessibility Statement</nav>
    <h1>Accessibility Statement</h1>
    <p class="lead-2 mt-2" style="max-width:44rem">Our commitment to WCAG 2.1 AA conformance across the InternMatch platform.</p>
  </div>
</header>
<section class="section">
  <div class="container">
    <div class="row g-5"><div class="col-lg-8">
      <h2 class="mt-4" style="font-size:1.25rem">Standards</h2>
      <p class="text-body-2">InternMatch targets WCAG 2.1 Level AA. Interfaces use semantic HTML5 landmarks, descriptive labels, and ARIA attributes where native semantics are insufficient.</p>
      <h2 class="mt-4" style="font-size:1.25rem">Keyboard and focus</h2>
      <p class="text-body-2">All interactive elements are reachable by keyboard, focus indicators are visible with a 3px high-contrast ring, and a skip-to-content link is provided on every page.</p>
      <h2 class="mt-4" style="font-size:1.25rem">Colour and contrast</h2>
      <p class="text-body-2">Body and heading text meet minimum contrast ratios against their backgrounds. Colour is never the sole carrier of meaning; status is always paired with text or an icon.</p>
      <h2 class="mt-4" style="font-size:1.25rem">Motion</h2>
      <p class="text-body-2">Animations are subtle and respect the prefers-reduced-motion setting, which disables non-essential motion entirely.</p>
      <h2 class="mt-4" style="font-size:1.25rem">Feedback</h2>
      <p class="text-body-2">If you encounter an accessibility barrier, contact the Practicum Office so we can address it.</p>
    </div><div class="col-lg-4"><div class="card-x" style="position:sticky;top:100px"><div class="card-x-head"><h3>Need help?</h3></div><div class="card-x-body"><p class="fs-13 text-muted-2">Reach the Practicum Office for clarifications about this page.</p><a href="{{ route('public.contact') }}" class="btn btn-brand btn-sm w-100">Contact us</a></div></div></div></div>
  </div>
</section>
@endsection
