@extends('layouts.public')
@section('title','Frequently Asked Questions')
@section('meta_description','Answers to the questions students, coordinators, and host establishments ask most.')
@section('content')
<header class="pt-5" style="background:var(--warm-gray);border-bottom:1px solid var(--border)">
  <div class="container" style="padding:6.5rem 0 3.5rem">
    <nav class="fs-12 text-muted-2 mb-2" aria-label="Breadcrumb"><a href="{{ route('landing') }}">Home</a> <span class="mx-1">/</span> Frequently Asked Questions</nav>
    <h1>Frequently Asked Questions</h1>
    <p class="lead-2 mt-2" style="max-width:44rem">Answers to the questions students, coordinators, and host establishments ask most.</p>
  </div>
</header>
<section class="section">
  <div class="container">
    <div class="row justify-content-center"><div class="col-lg-9">@include('partials.faq-list')</div></div>
  </div>
</section>
@endsection
