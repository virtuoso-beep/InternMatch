@extends('layouts.public')
@section('title','Terms & Conditions')
@section('meta_description','Conditions governing the use of the InternMatch platform.')
@section('content')
<header class="pt-5" style="background:var(--warm-gray);border-bottom:1px solid var(--border)">
  <div class="container" style="padding:6.5rem 0 3.5rem">
    <nav class="fs-12 text-muted-2 mb-2" aria-label="Breadcrumb"><a href="{{ route('landing') }}">Home</a> <span class="mx-1">/</span> Terms & Conditions</nav>
    <h1>Terms & Conditions</h1>
    <p class="lead-2 mt-2" style="max-width:44rem">Conditions governing the use of the InternMatch platform.</p>
  </div>
</header>
<section class="section">
  <div class="container">
    <div class="row g-5"><div class="col-lg-8">
      <h2 class="mt-4" style="font-size:1.25rem">Acceptance</h2>
      <p class="text-body-2">By accessing InternMatch you agree to these terms and to the policies of the University of Mindanao Tagum College.</p>
      <h2 class="mt-4" style="font-size:1.25rem">Accounts</h2>
      <p class="text-body-2">Accounts are issued by the university. You are responsible for keeping credentials confidential and for all activity performed under your account.</p>
      <h2 class="mt-4" style="font-size:1.25rem">Acceptable use</h2>
      <p class="text-body-2">Users must not misrepresent competencies, upload falsified documents, attempt unauthorised access, or extract data in bulk without authorisation.</p>
      <h2 class="mt-4" style="font-size:1.25rem">Recommendations</h2>
      <p class="text-body-2">Recommendations are decision-support outputs. Final placement decisions rest with the practicum coordinator and the host establishment.</p>
      <h2 class="mt-4" style="font-size:1.25rem">Availability</h2>
      <p class="text-body-2">The university may suspend access for maintenance, security, or academic policy reasons without prior notice.</p>
    </div><div class="col-lg-4"><div class="card-x" style="position:sticky;top:100px"><div class="card-x-head"><h3>Need help?</h3></div><div class="card-x-body"><p class="fs-13 text-muted-2">Reach the Practicum Office for clarifications about this page.</p><a href="{{ route('public.contact') }}" class="btn btn-brand btn-sm w-100">Contact us</a></div></div></div></div>
  </div>
</section>
@endsection
