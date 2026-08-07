@extends('layouts.public')
@section('title','About InternMatch')
@section('meta_description','An intelligent student internship placement and monitoring system developed for the University of Mindanao Tagum College.')
@section('content')
<header class="pt-5" style="background:var(--warm-gray);border-bottom:1px solid var(--border)">
  <div class="container" style="padding:6.5rem 0 3.5rem">
    <nav class="fs-12 text-muted-2 mb-2" aria-label="Breadcrumb"><a href="{{ route('landing') }}">Home</a> <span class="mx-1">/</span> About InternMatch</nav>
    <h1>About InternMatch</h1>
    <p class="lead-2 mt-2" style="max-width:44rem">An intelligent student internship placement and monitoring system developed for the University of Mindanao Tagum College.</p>
  </div>
</header>
<section class="section">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico "><i class="bi bi-bullseye"></i></div>
        <h3>Purpose</h3><p>Replace manual, spreadsheet-driven placement with competency-based, explainable matching.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico gold"><i class="bi bi-eye"></i></div>
        <h3>Vision</h3><p>A transparent practicum process trusted by students, faculty, and host establishments alike.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico lav"><i class="bi bi-flag"></i></div>
        <h3>Mission</h3><p>Give every role a shared source of truth from onboarding through completion and archiving.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico green"><i class="bi bi-diagram-3"></i></div>
        <h3>Scope</h3><p>Placement, monitoring, evaluation, analytics, and institutional reporting for the whole college.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico "><i class="bi bi-shield-check"></i></div>
        <h3>Compliance</h3><p>Aligned with CHED CMO No. 104 requirements and the Data Privacy Act of 2012.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico gold"><i class="bi bi-people"></i></div>
        <h3>Community</h3><p>Six distinct roles, each with an interface tuned to the decisions they make.</p></div></div>
    </div>
    <div class="row g-5"><div class="col-lg-8">
      <h2 class="mt-4" style="font-size:1.25rem">Background</h2>
      <p class="text-body-2">Practicum placement at UM Tagum College involves hundreds of students each term across multiple programs, each with different competency profiles, residence locations, and program requirements. Coordinating this manually is time-consuming and difficult to audit.</p>
      <p class="text-body-2">InternMatch introduces semantic competency matching, machine learning recommendation ranking, and geospatial accessibility analysis into a single monitored workflow.</p>
      <h2 class="mt-4" style="font-size:1.25rem">Institutional value</h2>
      <p class="text-body-2">Chairs and the dean gain measurable insight into competency coverage, company performance, deployment distribution, and completion rates, supporting accreditation and continuous curriculum improvement.</p>
    </div><div class="col-lg-4"><div class="card-x" style="position:sticky;top:100px"><div class="card-x-head"><h3>Need help?</h3></div><div class="card-x-body"><p class="fs-13 text-muted-2">Reach the Practicum Office for clarifications about this page.</p><a href="{{ route('public.contact') }}" class="btn btn-brand btn-sm w-100">Contact us</a></div></div></div></div>
  </div>
</section>
@endsection
