@extends('layouts.public')
@section('title','Privacy Policy')
@section('meta_description','How InternMatch collects, uses, and protects personal information under RA 10173.')
@section('content')
<header class="pt-5" style="background:var(--warm-gray);border-bottom:1px solid var(--border)">
  <div class="container" style="padding:6.5rem 0 3.5rem">
    <nav class="fs-12 text-muted-2 mb-2" aria-label="Breadcrumb"><a href="{{ route('landing') }}">Home</a> <span class="mx-1">/</span> Privacy Policy</nav>
    <h1>Privacy Policy</h1>
    <p class="lead-2 mt-2" style="max-width:44rem">How InternMatch collects, uses, and protects personal information under RA 10173.</p>
  </div>
</header>
<section class="section">
  <div class="container">
    <div class="row g-5"><div class="col-lg-8">
      <h2 class="mt-4" style="font-size:1.25rem">Information we collect</h2>
      <p class="text-body-2">Student records (name, program, year level, contact details, residence location), competency statements, uploaded requirement documents, internship logs and reports, and system usage data required for auditing.</p>
      <h2 class="mt-4" style="font-size:1.25rem">How we use information</h2>
      <p class="text-body-2">Personal data is used solely to generate internship recommendations, coordinate placement, monitor internship progress, and produce institutional reports. Residence coordinates are used only to compute accessibility scores.</p>
      <h2 class="mt-4" style="font-size:1.25rem">Sharing</h2>
      <p class="text-body-2">Data is shared with accredited host establishments only for students endorsed to them, and with university personnel according to their assigned role. Data is never sold or used for advertising.</p>
      <h2 class="mt-4" style="font-size:1.25rem">Retention and rights</h2>
      <p class="text-body-2">Records are retained for the period required by university and CHED policy, then archived. Students may request access, correction, or deletion of their personal data through the Practicum Office, in line with the Data Privacy Act of 2012.</p>
      <h2 class="mt-4" style="font-size:1.25rem">Security</h2>
      <p class="text-body-2">Access is role-based, sensitive actions are recorded in an audit trail, and administrative operations require elevated permissions.</p>
    </div><div class="col-lg-4"><div class="card-x" style="position:sticky;top:100px"><div class="card-x-head"><h3>Need help?</h3></div><div class="card-x-body"><p class="fs-13 text-muted-2">Reach the Practicum Office for clarifications about this page.</p><a href="{{ route('public.contact') }}" class="btn btn-brand btn-sm w-100">Contact us</a></div></div></div></div>
  </div>
</section>
@endsection
