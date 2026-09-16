@extends('layouts.app', ['role' => 'student', 'active' => 'competencies'])
@section('title','Competencies & skills')
@php($pageTitle = 'Competencies & skills')
@php($breadcrumbs = ['Competencies & skills'])

@section('content')
@include('components.page-header', [
  'title' => 'Competencies & skills',
  'subtitle' => 'Analyzed against internship task descriptions using semantic matching.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-plus-lg me-1'></i>Add competency</button>",
])

<div class="row g-3">
  <div class="col-xl-8">
    <section class="card-x mb-3">
      <div class="card-x-head"><h3>Add a competency</h3></div>
      <div class="card-x-body">
        <form data-demo>
        <label class="form-label" for="cmp">Describe a skill in your own words</label>
        <textarea class="form-control" id="cmp" rows="3" placeholder="e.g. Built a REST API using Laravel and MySQL for a barangay records system, including authentication and role-based access."></textarea>
        <p class="form-hint">Written statements match better than single keywords — the engine compares meaning, not tags.</p>
        <button class="btn btn-brand btn-sm mt-2">Add competency</button>
        </form>
      </div>
    </section>
    <section class="card-x mb-3">
      <div class="card-x-head"><h3>Declared technical skills</h3><span class="ms-auto fs-12 text-muted-2">9 skills</span></div>
      <div class="card-x-body d-flex flex-wrap gap-2">
        @foreach(['Web Development','Database Design','Laravel','PHP','JavaScript','REST APIs','Git & GitHub','UI Design','Technical Writing'] as $s)
          <span class="chip chip-skill">{{ $s }} <i class="bi bi-x-lg fs-11"></i></span>
        @endforeach
      </div>
    </section>
    <section class="card-x">
      <div class="card-x-head"><h3>Competency strength</h3><span class="badge-x badge-ai ms-2"><i class="bi bi-cpu"></i> Semantic score</span></div>
      <div class="card-x-body d-grid gap-3">
        @foreach([['Back-end development',92],['Database design & SQL',88],['Front-end fundamentals',74],['Requirements analysis',66],['Technical documentation',58],['Data visualization',41]] as $c)
          @include('components.progress', ['value'=>$c[1],'tone'=>'lav','label'=>$c[0]])
        @endforeach
      </div>
    </section>
  </div>
  <div class="col-xl-4">
    <section class="card-x mb-3"><div class="card-x-head"><h3>Soft skills</h3></div><div class="card-x-body d-flex flex-wrap gap-2">
      @foreach(['Communication','Teamwork','Time management','Adaptability','Attention to detail'] as $s)<span class="chip">{{ $s }}</span>@endforeach
    </div></section>
    <section class="card-x mb-3"><div class="card-x-head"><h3>Gap analysis</h3></div><div class="card-x-body">
      @include('components.alert',['type'=>'ai','title'=>'Two skills would raise your match rate','message'=>'Adding <strong>automated testing</strong> and <strong>cloud deployment</strong> would qualify you for 14 additional opportunities.'])
      <div class="d-flex flex-wrap gap-2">@foreach(['Unit testing','Docker','CI/CD','Cloud deployment'] as $s)<span class="chip chip-ai">+ {{ $s }}</span>@endforeach</div>
    </div></section>
    <section class="card-x"><div class="card-x-head"><h3>Validation</h3></div><div class="card-x-body">
      @include('components.confidence-meter',['value'=>4,'label'=>'Coordinator validated'])
      <p class="fs-12 text-muted-2 mt-2 mb-0">7 of 9 declared skills have supporting portfolio evidence.</p>
    </div></section>
  </div>
</div>
@endsection
