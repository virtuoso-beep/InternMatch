@extends('layouts.public')
@section('title','How It Works')
@section('meta_description','A guided, eight-stage workflow with human approval at every critical decision point.')
@section('content')
<header class="pt-5" style="background:var(--warm-gray);border-bottom:1px solid var(--border)">
  <div class="container" style="padding:6.5rem 0 3.5rem">
    <nav class="fs-12 text-muted-2 mb-2" aria-label="Breadcrumb"><a href="{{ route('landing') }}">Home</a> <span class="mx-1">/</span> How It Works</nav>
    <h1>How It Works</h1>
    <p class="lead-2 mt-2" style="max-width:44rem">A guided, eight-stage workflow with human approval at every critical decision point.</p>
  </div>
</header>
<section class="section">
  <div class="container">
    <div class="flow mb-5">
      @foreach([['Student Registration','Verified enrollment creates the intern account.'],['Profile Completion','Program, residence, and preferences captured.'],['Competency Assessment','Skills declared, validated, and weighted.'],['AI Recommendation','Ranked establishments with explanations.'],['Coordinator Approval','Human review before any deployment.'],['Company Assignment','Slot reserved, MOA and endorsement issued.'],['Internship Monitoring','Logs, reports, attendance, evaluations.'],['Completion','Final report, hours verified, records archived.']] as $i => $s)
        <div class="flow-step reveal"><div class="n">{{ $i+1 }}</div><h4>{{ $s[0] }}</h4><p>{{ $s[1] }}</p></div>
      @endforeach
    </div>
    <div class="card-x reveal">
      <div class="card-x-head"><h3>Worked example — how a 92% match is produced</h3></div>
      <div class="card-x-body">
        <div class="row g-4">
          <div class="col-lg-6">
            @foreach([['Competency similarity',92,'lav'],['Accessibility score',88,'green'],['Capacity availability',75,'gold'],['Historical acceptance',81,'']] as $m)
              <div class="mb-3">@include('components.progress', ['value'=>$m[1],'tone'=>$m[2],'label'=>$m[0]])</div>
            @endforeach
          </div>
          <div class="col-lg-6">
            <p class="fs-13 text-muted-2">Each signal is normalised, weighted per program policy, and combined into a single ranked score. Coordinators can override any ranking, and every override is recorded in the audit trail.</p>
            <div class="d-flex flex-wrap gap-2">
              @foreach(['Laravel','REST APIs','MySQL','Requirements analysis','Technical documentation'] as $c)<span class="chip chip-ai">{{ $c }}</span>@endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
