@extends('layouts.public')
@section('title','Intelligent Internship Placement')
@section('meta_description','InternMatch transforms internship placement at UM Tagum College through semantic competency matching, machine learning recommendations and geospatial accessibility analysis.')

@section('content')

{{-- 1. HERO --}}
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="hero-badge mb-3"><i class="bi bi-stars text-gold"></i> Capstone Project · UM Tagum College</span>
        <h1>Transforming internship placement through intelligent matching.</h1>
        <p class="sub mt-3">InternMatch pairs student competencies with host establishment requirements using semantic matching, machine learning recommendations, and geospatial accessibility analysis — then monitors every placement to completion.</p>
        <div class="d-flex flex-wrap gap-2 mt-4">
          <a href="{{ route('login') }}" class="btn btn-brand btn-lg">Login to InternMatch <i class="bi bi-arrow-right ms-1"></i></a>
          <a href="#features" class="btn btn-light-outline btn-lg">Explore Features</a>
        </div>
        <div class="d-flex flex-wrap gap-4 mt-5">
          @foreach([['1,240','Student interns'],['186','Partner companies'],['412','Opportunities'],['96%','Successful placements']] as $s)
            <div><b class="text-white d-block" style="font-family:Poppins;font-size:1.5rem">{{ $s[0] }}</b>
            <span style="font-size:.75rem;color:rgba(255,255,255,.62)">{{ $s[1] }}</span></div>
          @endforeach
        </div>
      </div>

      <div class="col-lg-6">
        <div class="position-relative">
          <div class="glass p-3 p-md-4 float-a">
            <div class="d-flex align-items-center gap-2 mb-3">
              <span class="badge-x badge-ai"><i class="bi bi-cpu"></i> Recommendation engine</span>
              <span class="ms-auto" style="font-size:.7rem;color:rgba(255,255,255,.6)">Updated 2 min ago</span>
            </div>
            @foreach([['DataCore Solutions Inc.','Software Development · 3.2 km',92],['Tagum City IT Office','Systems Support · 1.8 km',87],['Northline Analytics','Data Engineering · 6.4 km',81]] as $r)
              <div class="d-flex align-items-center gap-3 p-3 mb-2" style="background:rgba(255,255,255,.92);border-radius:12px">
                <span class="avatar gold">{{ strtoupper(substr($r[0],0,2)) }}</span>
                <div class="flex-grow-1">
                  <div class="text-heading fw-semibold" style="font-size:.86rem">{{ $r[0] }}</div>
                  <div class="fs-11 text-muted-2">{{ $r[1] }}</div>
                </div>
                <span class="match-pill {{ $r[2] >= 85 ? '' : 'mid' }}">{{ $r[2] }}%</span>
              </div>
            @endforeach
            <div class="p-3" style="background:rgba(255,255,255,.92);border-radius:12px">
              <div class="d-flex justify-content-between fs-12 mb-1"><span class="text-body-2">Competency coverage</span><span class="text-heading fw-semibold">86%</span></div>
              <div class="prog lav"><span style="width:86%"></span></div>
            </div>
          </div>
          <div class="glass-stat position-absolute float-b d-none d-md-block" style="bottom:-24px;left:-24px">
            <b data-count="96" data-suffix="%">0%</b><span>Placement rate</span>
          </div>
          <div class="glass-stat position-absolute float-b d-none d-md-block" style="top:-22px;right:-14px">
            <b data-count="186">0</b><span>Partner establishments</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- 2. ABOUT --}}
<section class="section" id="about">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">About InternMatch</span>
      <h2 class="mt-2">Built for the practicum realities of UM Tagum College</h2>
      <p>Manual internship placement is slow, subjective, and difficult to monitor. InternMatch replaces spreadsheets with an intelligent, auditable, institution-wide platform.</p>
    </div>
    <div class="row g-4">
      @foreach([
        ['bullseye','Purpose','Match every student to a host establishment that fits their competencies, program requirements, and realistic travel accessibility.'],
        ['eye','Vision','A transparent placement process where every recommendation can be explained, reviewed, and defended by coordinators.'],
        ['flag','Mission','Support students, coordinators, supervisors, chairs, and the dean with one shared source of truth from onboarding to completion.'],
        ['list-check','Objectives','Automate matching, monitor deployment, measure competency coverage, and generate CHED-aligned institutional reports.'],
      ] as $c)
      <div class="col-md-6 col-xl-3 reveal">
        <div class="feature-card">
          <div class="feature-ico"><i class="bi bi-{{ $c[0] }}"></i></div>
          <h3>{{ $c[1] }}</h3><p>{{ $c[2] }}</p>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- 3. FEATURES --}}
<section class="section alt" id="features">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">Why choose InternMatch</span>
      <h2 class="mt-2">Everything the practicum office needs, in one platform</h2>
      <p>Designed with the same care as the enterprise tools your students will use in the workplace.</p>
    </div>
    <div class="row g-4">
      @foreach([
        ['magic','','Intelligent Matching','Semantic analysis compares student competency statements with actual internship task descriptions — not just keyword tags.'],
        ['cpu','lav','Machine Learning Recommendations','Ranking learns from historical placements, acceptance outcomes, and supervisor evaluations.'],
        ['stars','gold','Competency-Based Placement','Every skill is scored, weighted, and mapped to program outcomes before a match is proposed.'],
        ['geo-alt','green','Accessibility Analysis','Geospatial scoring factors distance, transport routes, and commute cost into every recommendation.'],
        ['activity','','Internship Monitoring','Daily logs, weekly reports, attendance, and evaluations tracked against required hours.'],
        ['bar-chart','lav','Analytics Dashboard','Deployment, completion, and utilization analytics for chairs, coordinators, and the dean.'],
        ['file-earmark-bar-graph','gold','Digital Reports','Export institutional reports in CSV, Excel, or print-ready PDF in a single click.'],
        ['shield-lock','green','Secure User Management','Role-based permissions, audit trails, and account lifecycle controls for administrators.'],
      ] as $f)
      <div class="col-md-6 col-xl-3 reveal">
        <div class="feature-card">
          <div class="feature-ico {{ $f[1] }}"><i class="bi bi-{{ $f[0] }}"></i></div>
          <h3>{{ $f[2] }}</h3><p>{{ $f[3] }}</p>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- 4. WORKFLOW --}}
<section class="section" id="workflow">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">System workflow</span>
      <h2 class="mt-2">From registration to completion</h2>
      <p>Eight guided stages keep students, coordinators, and supervisors aligned throughout the practicum term.</p>
    </div>
    <div class="flow reveal">
      @foreach([
        ['Student Registration','Verified enrollment creates the intern account.'],
        ['Profile Completion','Program, residence, and preferences captured.'],
        ['Competency Assessment','Skills declared, validated, and weighted.'],
        ['AI Recommendation','Ranked establishments with explanations.'],
        ['Coordinator Approval','Human review before any deployment.'],
        ['Company Assignment','Slot reserved, MOA and endorsement issued.'],
        ['Internship Monitoring','Logs, reports, attendance, evaluations.'],
        ['Completion','Final report, hours verified, records archived.'],
      ] as $i => $s)
        <div class="flow-step">
          <div class="n">{{ $i + 1 }}</div>
          <h4>{{ $s[0] }}</h4><p>{{ $s[1] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- 5. HOW AI WORKS --}}
<section class="section alt" id="ai">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-5 reveal">
        <span class="eyebrow">How the intelligence works</span>
        <h2 class="mt-2">Explainable by design</h2>
        <p class="text-muted-2">Every recommendation exposes the evidence behind it: which competency matched which task, how far the site is, and how confident the model is. Coordinators approve decisions — the model only ranks them.</p>
        <a href="{{ route('public.how-it-works') }}" class="btn btn-brand mt-2">See a worked example <i class="bi bi-arrow-right ms-1"></i></a>
      </div>
      <div class="col-lg-7">
        <div class="row g-3">
          @foreach([
            ['1','Student skills','Competency statements, certifications, portfolio evidence.','person-badge',''],
            ['2','Semantic matching','Embeddings compare meaning, not keywords, against task descriptions.','diagram-3','lav'],
            ['3','ML recommendation','Historical outcomes reweigh the ranking per program.','cpu','lav'],
            ['4','Accessibility analysis','Distance, routes, and commute cost adjust feasibility.','geo-alt','green'],
            ['5','Final recommendation','A ranked, explainable shortlist for coordinator review.','check2-circle','gold'],
          ] as $s)
          <div class="col-md-6 reveal">
            <div class="feature-card d-flex gap-3 align-items-start">
              <div class="feature-ico {{ $s[4] }} mb-0"><i class="bi bi-{{ $s[3] }}"></i></div>
              <div><span class="fs-11 text-muted-2 d-block">Step {{ $s[0] }}</span>
              <h3 class="mb-1">{{ $s[1] }}</h3><p>{{ $s[2] }}</p></div>
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>

{{-- 6. BENEFITS --}}
<section class="section">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">University benefits</span>
      <h2 class="mt-2">One platform, six perspectives</h2>
      <p>Each role gets a workspace tuned to the decisions they actually make.</p>
    </div>
    <div class="row g-4">
      @foreach([
        ['mortarboard','Students','Transparent recommendations, requirement tracking, and progress visibility.'],
        ['clipboard-check','Practicum Coordinators','Bulk approvals, slot management, MOA tracking, and monitoring in one queue.'],
        ['buildings','Host Establishments','Intern rosters, attendance, and structured evaluation forms.'],
        ['diagram-3','Program Chairs','Competency coverage and company performance per program.'],
        ['graph-up-arrow','Dean','Executive analytics across colleges, placement distribution and completion rates.'],
        ['shield-lock','Administration','Account lifecycle, audit trails, academic periods, and backups.'],
      ] as $b)
      <div class="col-md-6 col-lg-4 reveal">
        <div class="feature-card">
          <div class="feature-ico gold"><i class="bi bi-{{ $b[0] }}"></i></div>
          <h3>{{ $b[1] }}</h3><p>{{ $b[2] }}</p>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- 7. KEY FEATURE SHOWCASE --}}
<section class="section alt">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">Inside the platform</span>
      <h2 class="mt-2">Interfaces designed for daily work</h2>
    </div>
    <div class="reveal">
      <div class="nav-x justify-content-center mb-4" data-tabs="#showcase">
        <a href="#" class="active" data-tab-target="rec">Recommendations</a>
        <a href="#" data-tab-target="mon">Monitoring</a>
        <a href="#" data-tab-target="ana">Analytics</a>
        <a href="#" data-tab-target="map">Accessibility</a>
      </div>
      <div id="showcase">
        @foreach([
          ['rec','Ranked, explainable recommendations','Every card shows competency similarity, accessibility score, and remaining slot capacity side by side.'],
          ['mon','Live internship monitoring','Track required hours, weekly reports, and supervisor evaluations against the academic calendar.'],
          ['ana','Institutional analytics','Deployment trends, completion rates, and company utilization rendered for decision makers.'],
          ['map','Geospatial accessibility','Plot establishments against student residences to surface realistic commute options.'],
        ] as $i => $t)
        <div data-tab-panel="{{ $t[0] }}" class="{{ $i ? 'd-none' : '' }}">
          <div class="card-x">
            <div class="card-x-body">
              <div class="row g-4 align-items-center">
                <div class="col-lg-5">
                  <h3>{{ $t[1] }}</h3>
                  <p class="text-muted-2 fs-13">{{ $t[2] }}</p>
                  <a href="{{ route('login') }}" class="btn btn-brand-soft btn-sm">Open in app</a>
                </div>
                <div class="col-lg-7">
                  <div class="chart-ph"><span><i class="bi bi-window-stack fs-3 d-block mb-2"></i>Interface preview placeholder</span></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</section>

{{-- 8. STATISTICS --}}
<section class="section" style="background:linear-gradient(160deg,#7D1F26,#4a141a)">
  <div class="container">
    <div class="section-head reveal" style="color:#fff">
      <span class="eyebrow" style="color:var(--um-gold-soft)">By the numbers</span>
      <h2 class="mt-2 text-white">Adoption across the college</h2>
      <p style="color:rgba(255,255,255,.7)">Figures shown are illustrative placeholders for demonstration.</p>
    </div>
    <div class="row g-4 text-center reveal">
      @foreach([[186,'','Partner establishments'],[1240,'','Student interns'],[412,'','Internship opportunities'],[96,'%','Placement rate'],[1680,'','System users'],[978,'','Completed internships']] as $s)
        <div class="col-6 col-lg-2">
          <div class="glass p-3">
            <b class="d-block text-white" style="font-family:Poppins;font-size:1.8rem" data-count="{{ $s[0] }}" data-suffix="{{ $s[1] }}">0</b>
            <span style="font-size:.74rem;color:rgba(255,255,255,.7)">{{ $s[2] }}</span>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- 9. PARTNERS --}}
<section class="section" id="partners">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">Partner establishments</span>
      <h2 class="mt-2">Trusted host establishments across Davao del Norte</h2>
    </div>
  </div>
  <div class="logo-strip reveal">
    <div class="logo-track">
      @foreach(array_merge($p = ['DataCore Solutions','Tagum City IT Office','Northline Analytics','Davao Agri Ventures','BrightPath BPO','Sunrise Cooperative','MetroLink Logistics','Provincial Capitol','Anflo Industrial Estate','GreenHarvest Corp'], $p) as $c)
        <div class="logo-pill"><span class="m">{{ strtoupper(substr($c,0,2)) }}</span>{{ $c }}</div>
      @endforeach
    </div>
  </div>
</section>

{{-- 10. TESTIMONIALS --}}
<section class="section alt">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">Testimonials</span>
      <h2 class="mt-2">What the practicum community says</h2>
    </div>
    <div class="row g-4">
      @foreach([
        ['TT','Trisha Talamillo','BSIT Student Intern','I could finally see why a company was recommended to me — and how far it actually was from home.'],
        ['MC','Ms. Marissa Cortez','Practicum Coordinator','Approvals that used to take a week of spreadsheet work now take one afternoon.'],
        ['RF','Mr. Rico Fernandez','Host Supervisor, DataCore','Attendance and evaluations are in one place, so I spend my time mentoring instead of filing.'],
      ] as $t)
      <div class="col-lg-4 reveal">
        <div class="quote-card">
          <div class="stars mb-2">★★★★★</div>
          <p>“{{ $t[3] }}”</p>
          <div class="d-flex align-items-center gap-2 mt-3">
            <span class="avatar">{{ $t[0] }}</span>
            <span><span class="d-block text-heading fw-semibold" style="font-size:.85rem">{{ $t[1] }}</span>
            <span class="fs-11 text-muted-2">{{ $t[2] }}</span></span>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- 11. FAQ --}}
<section class="section" id="faq">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-4 reveal">
        <span class="eyebrow">FAQ</span>
        <h2 class="mt-2">Frequently asked questions</h2>
        <p class="text-muted-2">Can't find an answer? The practicum office is one message away.</p>
        <a href="{{ route('public.contact') }}" class="btn btn-ghost btn-sm">Contact the office</a>
      </div>
      <div class="col-lg-8 reveal">
        @include('partials.faq-list')
      </div>
    </div>
  </div>
</section>

{{-- 12. CONTACT --}}
<section class="section alt" id="contact">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-5 reveal">
        <span class="eyebrow">Contact</span>
        <h2 class="mt-2">Visit the Practicum Office</h2>
        <ul class="list-unstyled d-grid gap-3 mt-4 fs-13">
          @foreach([
            ['geo-alt','University of Mindanao Tagum College','Apokon Road, Tagum City, Davao del Norte 8100'],
            ['telephone','(084) 216-0000 local 118','Mobile: +63 917 000 0000'],
            ['envelope','internmatch@umindanao.edu.ph','practicum.tagum@umindanao.edu.ph'],
            ['clock','Office hours','Monday to Friday · 8:00 AM – 5:00 PM'],
          ] as $c)
          <li class="d-flex gap-3">
            <span class="feature-ico mb-0" style="width:38px;height:38px;font-size:.95rem"><i class="bi bi-{{ $c[0] }}"></i></span>
            <span><strong class="d-block text-heading">{{ $c[1] }}</strong><span class="text-muted-2">{{ $c[2] }}</span></span>
          </li>
          @endforeach
        </ul>
        <div class="map-ph mt-4">
          <div class="map-pin" style="top:44%;left:46%"><b>UM</b></div>
          <span class="position-absolute bottom-0 start-0 m-2 fs-11 text-muted-2">Google Maps embed placeholder</span>
        </div>
      </div>
      <div class="col-lg-7 reveal">
        <div class="card-x">
          <div class="card-x-head"><h3>Send a message</h3></div>
          <div class="card-x-body">
            <form class="row g-3" data-demo>
              <div class="col-md-6"><div class="form-floating"><input class="form-control" id="cf1" placeholder="Name" required><label for="cf1">Full name</label></div></div>
              <div class="col-md-6"><div class="form-floating"><input type="email" class="form-control" id="cf2" placeholder="Email" required><label for="cf2">Email address</label></div></div>
              <div class="col-md-6"><div class="form-floating"><select class="form-select" id="cf3"><option>Student</option><option>Host establishment</option><option>Faculty</option><option>Other</option></select><label for="cf3">I am a…</label></div></div>
              <div class="col-md-6"><div class="form-floating"><input class="form-control" id="cf4" placeholder="Subject"><label for="cf4">Subject</label></div></div>
              <div class="col-12"><div class="form-floating"><textarea class="form-control" id="cf5" style="height:130px" placeholder="Message"></textarea><label for="cf5">Message</label></div></div>
              <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                <button class="btn btn-brand" type="submit">Send message</button>
                <span class="fs-12 text-muted-2">We usually reply within one working day.</span>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- CTA --}}
<section class="section" style="padding:4rem 0">
  <div class="container">
    <div class="card-x reveal" style="background:linear-gradient(140deg,#A2272C,#7D1F26);border:0">
      <div class="card-x-body d-flex flex-wrap align-items-center gap-3 p-4 p-md-5">
        <div class="flex-grow-1">
          <h2 class="text-white mb-1" style="color:#fff">Ready to begin your internship journey?</h2>
          <p class="mb-0" style="color:rgba(255,255,255,.8)">Sign in with your UM Tagum College account to access your workspace.</p>
        </div>
        <a href="{{ route('login') }}" class="btn btn-gold btn-lg">Login to InternMatch</a>
      </div>
    </div>
  </div>
</section>
@endsection
