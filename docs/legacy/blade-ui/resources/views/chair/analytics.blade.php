@extends('layouts.app', ['role' => 'chair', 'active' => 'analytics'])
@section('title','Program analytics')
@php($pageTitle = 'Program analytics')
@php($breadcrumbs = ['Program analytics'])

@section('content')
@include('components.page-header', [
  'title' => 'Program analytics',
  'subtitle' => 'Trends across the programs you supervise.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Recommendation acceptance','value'=>'84%','icon'=>'hand-thumbs-up','tone'=>'lav','delta'=>'+3%','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Avg. match score','value'=>'86%','icon'=>'magic','tone'=>'brand','delta'=>'+2%','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Avg. commute','value'=>'4.6 km','icon'=>'geo-alt','tone'=>'gold','delta'=>'-0.4','deltaDir'=>'down'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Supervisor rating','value'=>'4.5','icon'=>'star','tone'=>'green','delta'=>'+0.1','deltaDir'=>'up'])</div>
</div><div class="row g-3"><div class="col-xl-6"><section class="card-x h-100">
  <div class="card-x-head"><h3>Match score distribution</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b lav" style="height:8%"></div><div class="b lav" style="height:18%"></div><div class="b lav" style="height:34%"></div><div class="b lav" style="height:62%"></div><div class="b lav" style="height:48%"></div></div>
    <div class="chart-x-labels"><span><60</span><span>60-69</span><span>70-79</span><span>80-89</span><span>90+</span></div>
  </div>
</section></div><div class="col-xl-6"><section class="card-x h-100">
  <div class="card-x-head"><h3>Placements per month</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b " style="height:62%"></div><div class="b " style="height:88%"></div><div class="b " style="height:74%"></div><div class="b " style="height:52%"></div><div class="b " style="height:34%"></div></div>
    <div class="chart-x-labels"><span>Jun</span><span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span></div>
  </div>
</section></div></div>
@endsection
