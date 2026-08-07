@extends('layouts.public')
@section('title','Help Center')
@section('meta_description','Guides and quick answers for every InternMatch role.')
@section('content')
<header class="pt-5" style="background:var(--warm-gray);border-bottom:1px solid var(--border)">
  <div class="container" style="padding:6.5rem 0 3.5rem">
    <nav class="fs-12 text-muted-2 mb-2" aria-label="Breadcrumb"><a href="{{ route('landing') }}">Home</a> <span class="mx-1">/</span> Help Center</nav>
    <h1>Help Center</h1>
    <p class="lead-2 mt-2" style="max-width:44rem">Guides and quick answers for every InternMatch role.</p>
  </div>
</header>
<section class="section">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico "><i class="bi bi-mortarboard"></i></div>
        <h3>Student guide</h3><p>Completing your profile, declaring competencies, and submitting requirements.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico gold"><i class="bi bi-clipboard-check"></i></div>
        <h3>Coordinator guide</h3><p>Reviewing recommendations, approving deployments, and managing slots.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico lav"><i class="bi bi-buildings"></i></div>
        <h3>Supervisor guide</h3><p>Recording attendance and submitting intern evaluations.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico green"><i class="bi bi-bar-chart"></i></div>
        <h3>Analytics guide</h3><p>Reading deployment, completion, and coverage dashboards.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico "><i class="bi bi-shield-lock"></i></div>
        <h3>Administrator guide</h3><p>Managing accounts, roles, academic years, and backups.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico gold"><i class="bi bi-life-preserver"></i></div>
        <h3>Troubleshooting</h3><p>Login problems, upload errors, and notification settings.</p></div></div>
    </div>
    <div class="mt-5"><h2 style="font-size:1.25rem" class="mb-3">Popular questions</h2>@include('partials.faq-list')</div>
  </div>
</section>
@endsection
