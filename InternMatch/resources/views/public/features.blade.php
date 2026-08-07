@extends('layouts.public')
@section('title','Features')
@section('meta_description','Every module in InternMatch, from competency profiling to institutional analytics.')
@section('content')
<header class="pt-5" style="background:var(--warm-gray);border-bottom:1px solid var(--border)">
  <div class="container" style="padding:6.5rem 0 3.5rem">
    <nav class="fs-12 text-muted-2 mb-2" aria-label="Breadcrumb"><a href="{{ route('landing') }}">Home</a> <span class="mx-1">/</span> Features</nav>
    <h1>Features</h1>
    <p class="lead-2 mt-2" style="max-width:44rem">Every module in InternMatch, from competency profiling to institutional analytics.</p>
  </div>
</header>
<section class="section">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico "><i class="bi bi-magic"></i></div>
        <h3>Semantic Matching</h3><p>Meaning-based comparison of student competencies and internship task descriptions.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico gold"><i class="bi bi-cpu"></i></div>
        <h3>ML Recommendations</h3><p>Ranking informed by historical placements and supervisor evaluations.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico lav"><i class="bi bi-lightbulb"></i></div>
        <h3>Recommendation Explanation</h3><p>Transparent evidence panels for every ranked suggestion.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico green"><i class="bi bi-geo-alt"></i></div>
        <h3>Accessibility Analysis</h3><p>Distance, routes, and commute cost factored into feasibility.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico "><i class="bi bi-briefcase"></i></div>
        <h3>Opportunity & Slot Management</h3><p>Publish opportunities, control capacity, and track MOA validity.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico gold"><i class="bi bi-clipboard-check"></i></div>
        <h3>Monitoring & Evaluation</h3><p>Attendance, daily logs, weekly reports, and structured evaluations.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico lav"><i class="bi bi-bar-chart"></i></div>
        <h3>Analytics</h3><p>Deployment, completion, utilization, and coverage dashboards.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico green"><i class="bi bi-file-earmark-bar-graph"></i></div>
        <h3>Reports</h3><p>Export institution-ready reports in CSV, Excel, or PDF.</p></div></div>
      <div class="col-md-6 col-lg-4 reveal"><div class="feature-card">
        <div class="feature-ico "><i class="bi bi-shield-lock"></i></div>
        <h3>Administration</h3><p>Roles, permissions, academic periods, audit trails, and backups.</p></div></div>
    </div>
  </div>
</section>
@endsection
