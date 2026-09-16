@extends('layouts.app', ['role' => 'student', 'active' => 'progress'])
@section('title','Progress tracker')
@php($pageTitle = 'Progress tracker')
@php($breadcrumbs = ['Progress tracker'])

@section('content')
@include('components.page-header', [
  'title' => 'Progress tracker',
  'subtitle' => 'Hours, requirements, reports, and evaluations in one view.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Hours rendered','value'=>'312 / 486','icon'=>'clock-history','tone'=>'brand'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Weeks completed','value'=>'8 of 14','icon'=>'calendar3','tone'=>'gold'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Reports approved','value'=>'7 of 8','icon'=>'journal-check','tone'=>'green'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Evaluation average','value'=>'4.6 / 5','icon'=>'star','tone'=>'lav','delta'=>'+0.3','deltaDir'=>'up'])</div>
</div><div class="row g-3"><div class="col-xl-7"><section class="card-x h-100">
  <div class="card-x-head"><h3>Cumulative hours</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b " style="height:18%"></div><div class="b " style="height:46%"></div><div class="b " style="height:64%"></div><div class="b " style="height:86%"></div><div class="b " style="height:96%"></div></div>
    <div class="chart-x-labels"><span>Jun</span><span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span></div>
  </div>
</section></div><div class="col-xl-5"><section class="card-x h-100"><div class="card-x-head"><h3>Overall completion</h3></div>
  <div class="card-x-body text-center">
    <div class="ring green mx-auto" style="--val:64"><b>64%</b></div>
    <p class="fs-13 text-muted-2 mt-3 mb-0">On track for October 30 completion</p>
  </div></section></div></div>
@endsection
