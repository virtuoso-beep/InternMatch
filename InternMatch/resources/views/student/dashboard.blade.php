@extends('layouts.app', ['role' => 'student', 'active' => 'dashboard'])
@section('title','Welcome back, Trisha')
@php($pageTitle = 'Welcome back, Trisha')
@php($breadcrumbs = ['Dashboard'])

@section('content')
@include('components.page-header', [
  'title' => 'Welcome back, Trisha',
  'subtitle' => 'Here is where your internship stands today.',
  'actions' => "<a href='{{ route(\'student.recommendations\') }}' class='btn btn-brand btn-sm'><i class='bi bi-magic me-1'></i>View recommendations</a>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Profile completeness','value'=>'86%','icon'=>'person-badge','tone'=>'brand','delta'=>'+6%','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Hours rendered','value'=>'312 / 486','icon'=>'clock-history','tone'=>'gold'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Requirements submitted','value'=>'6 of 8','icon'=>'file-earmark-check','tone'=>'green'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'New recommendations','value'=>'3','icon'=>'magic','tone'=>'lav','delta'=>'+2','deltaDir'=>'up'])</div>
</div>
<div class="row g-3">
  <div class="col-xl-8">
    <section class="card-x mb-3">
      <div class="card-x-head"><h3>Top recommendation for you</h3><span class="badge-x badge-ai ms-2"><i class="bi bi-stars"></i> AI ranked</span>
        <a href="{{ route('student.recommendations') }}" class="ms-auto fs-12">View all</a></div>
      <div class="card-x-body">
        @include('components.rec-card', ['initials'=>'DC','company'=>'DataCore Solutions Inc.','match'=>92,'slots'=>2,'track'=>'Software Development','location'=>'Tagum City','distance'=>'3.2','access'=>88,'capacity'=>75,'skills'=>['Laravel','MySQL','REST APIs','Git']])
      </div>
    </section>
    <section class="card-x h-100">
  <div class="card-x-head"><h3>Weekly hours rendered</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b " style="height:45%"></div><div class="b " style="height:62%"></div><div class="b " style="height:58%"></div><div class="b " style="height:74%"></div><div class="b " style="height:66%"></div><div class="b " style="height:81%"></div><div class="b " style="height:70%"></div><div class="b " style="height:88%"></div></div>
    <div class="chart-x-labels"><span>W1</span><span>W2</span><span>W3</span><span>W4</span><span>W5</span><span>W6</span><span>W7</span><span>W8</span></div>
  </div>
</section>
  </div>
  <div class="col-xl-4">
    <section class="card-x mb-3">
      <div class="card-x-head"><h3>Internship progress</h3></div>
      <div class="card-x-body text-center">
        <div class="ring mx-auto" style="--val:64"><b>64%</b></div>
        <p class="fs-13 text-muted-2 mt-3 mb-0">312 of 486 required hours at DataCore Solutions Inc.</p>
      </div>
      <div class="card-x-foot"><a href="{{ route('student.progress') }}" class="btn btn-brand-soft btn-sm w-100">Open progress tracker</a></div>
    </section>
    <section class="card-x">
      <div class="card-x-head"><h3>Upcoming</h3></div>
      <div class="card-x-body">
        @include('components.timeline', ['items'=>[
          ['title'=>'Weekly Report #09 due','text'=>'Friday, 5:00 PM','state'=>'active'],
          ['title'=>'Midterm evaluation','text'=>'Supervisor submits next Monday','state'=>''],
          ['title'=>'Medical certificate','text'=>'Pending upload','state'=>''],
          ['title'=>'Orientation completed','text'=>'June 16, 2026','state'=>'done'],
        ]])
      </div>
    </section>
  </div>
</div>
@endsection
