<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Sign in') · InternMatch</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('css/internmatch.css') }}" rel="stylesheet">
</head>
<body>
<div class="auth-split">
  <aside class="auth-art d-none d-lg-flex" aria-hidden="true">
    <div>
      <a href="{{ route('landing') }}" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none">
        <span class="sb-mark">IM</span>
        <span><strong class="d-block" style="font-family:'Poppins'">InternMatch</strong>
        <span style="font-size:.7rem;opacity:.7">University of Mindanao Tagum College</span></span>
      </a>
    </div>
    <div>
      <div class="seal-ring mb-4">UM</div>
      <h2 class="text-white" style="font-size:2rem;max-width:22rem;line-height:1.2">@yield('art_title', 'Intelligent internship placement, built for UM Tagum.')</h2>
      <p style="color:rgba(255,255,255,.75);max-width:26rem" class="mt-3">@yield('art_text', 'Competency-based semantic matching, machine learning recommendations, and geospatial accessibility analysis in one institutional platform.')</p>
      <div class="d-flex gap-4 mt-4">
        <div><b class="text-white d-block fs-4" style="font-family:'Poppins'">1,240</b><span style="font-size:.72rem;color:rgba(255,255,255,.6)">Student interns</span></div>
        <div><b class="text-white d-block fs-4" style="font-family:'Poppins'">186</b><span style="font-size:.72rem;color:rgba(255,255,255,.6)">Partner establishments</span></div>
        <div><b class="text-white d-block fs-4" style="font-family:'Poppins'">96%</b><span style="font-size:.72rem;color:rgba(255,255,255,.6)">Placement rate</span></div>
      </div>
    </div>
    <p style="font-size:.72rem;color:rgba(255,255,255,.45)">&copy; {{ date('Y') }} University of Mindanao Tagum College · CHED CMO No. 104 compliant</p>
  </aside>
  <section class="auth-panel">
    <div class="auth-box fade-up">@yield('content')</div>
  </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/internmatch.js') }}"></script>
</body>
</html>
