<footer class="pub-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span class="sb-mark">IM</span>
          <span><strong class="d-block text-white" style="font-family:Poppins">InternMatch</strong>
          <span style="font-size:.72rem;opacity:.7">University of Mindanao Tagum College</span></span>
        </div>
        <p style="font-size:.85rem;max-width:22rem">An intelligent student internship placement and monitoring system supporting competency-based matching, accessibility analysis, and institutional reporting.</p>
        <div class="d-flex gap-2 mt-3">
          @foreach(['facebook','twitter-x','linkedin','youtube'] as $s)
            <a class="social-ico" href="#" aria-label="{{ $s }}"><i class="bi bi-{{ $s }}"></i></a>
          @endforeach
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Quick links</h6>
        <a href="{{ route('public.about') }}">About</a>
        <a href="{{ route('public.features') }}">Features</a>
        <a href="{{ route('public.how-it-works') }}">How It Works</a>
        <a href="{{ route('public.contact') }}">Contact</a>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Resources</h6>
        <a href="{{ route('public.help') }}">Help Center</a>
        <a href="{{ route('public.faq') }}">FAQ</a>
        <a href="{{ route('login') }}">Student Login</a>
        <a href="{{ route('public.accessibility') }}">Accessibility</a>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Legal</h6>
        <a href="{{ route('public.privacy') }}">Privacy Policy</a>
        <a href="{{ route('public.terms') }}">Terms &amp; Conditions</a>
        <a href="{{ route('public.accessibility') }}">Accessibility Statement</a>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Contact</h6>
        <a href="#">Apokon Road, Tagum City</a>
        <a href="#">(084) 216-0000</a>
        <a href="#">internmatch@umindanao.edu.ph</a>
        <a href="#">Mon–Fri · 8:00–17:00</a>
      </div>
    </div>
    <div class="fbottom d-flex flex-wrap justify-content-between gap-2">
      <span>&copy; {{ date('Y') }} University of Mindanao Tagum College. All rights reserved.</span>
      <span>Developed as a capstone project · CHED CMO No. 104 aligned</span>
    </div>
  </div>
</footer>
