@extends('layouts.app', ['role' => 'student', 'active' => 'my-internship'])
@section('title','My internship')
@php($pageTitle = 'My internship')
@php($breadcrumbs = ['My internship'])

@section('content')
@include('components.page-header', [
  'title' => 'My internship',
  'subtitle' => 'Current placement and progress toward completion.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3 mb-3">
  <div class="col-xl-8">
    <section class="card-x h-100"><div class="card-x-head"><h3>DataCore Solutions Inc.</h3>@include('components.badge',['type'=>'success','label'=>'Deployed','icon'=>'check-circle'])</div>
      <div class="card-x-body">
        <p class="fs-13 text-muted-2">Software Development Track · Purok 3, Magugpo, Tagum City</p>
        <div class="divider my-3"></div>
        <div class="row g-3">
          @foreach([['Supervisor','Mr. Rico Fernandez'],['Start date','June 16, 2026'],['Expected end','October 30, 2026'],['Required hours','486 hours'],['Schedule','Mon–Fri · 8:00–17:00'],['MOA status','Active until May 2027']] as $d)
            <div class="col-md-4"><span class="fs-12 text-muted-2 d-block">{{ $d[0] }}</span><strong class="text-heading fs-13">{{ $d[1] }}</strong></div>
          @endforeach
        </div>
      </div>
    </section>
  </div>
  <div class="col-xl-4"><section class="card-x h-100"><div class="card-x-head"><h3>Hours rendered</h3></div>
  <div class="card-x-body text-center">
    <div class="ring  mx-auto" style="--val:64"><b>64%</b></div>
    <p class="fs-13 text-muted-2 mt-3 mb-0">312 of 486 required hours</p>
  </div></section></div>
</div>
<div class="row g-3">
  <div class="col-xl-7"><section class="card-x h-100">
  <div class="card-x-head"><h3>Monthly hours</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b alt" style="height:42%"></div><div class="b alt" style="height:78%"></div><div class="b alt" style="height:86%"></div><div class="b alt" style="height:92%"></div><div class="b alt" style="height:64%"></div></div>
    <div class="chart-x-labels"><span>Jun</span><span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span></div>
  </div>
</section></div>
  <div class="col-xl-5"><section class="card-x h-100"><div class="card-x-head"><h3>Milestones</h3></div><div class="card-x-body">
    @include('components.timeline',['items'=>[
      ['title'=>'Orientation completed','text'=>'June 16, 2026','state'=>'done'],
      ['title'=>'First monthly report approved','text'=>'July 31, 2026','state'=>'done'],
      ['title'=>'Midterm evaluation','text'=>'Scheduled next Monday','state'=>'active'],
      ['title'=>'Final report submission','text'=>'October 25, 2026','state'=>''],
      ['title'=>'Completion clearance','text'=>'October 30, 2026','state'=>''],
    ]])
  </div></section></div>
</div>
@endsection
