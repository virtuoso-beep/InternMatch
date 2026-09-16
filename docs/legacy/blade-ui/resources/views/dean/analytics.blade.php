@extends('layouts.app', ['role' => 'dean', 'active' => 'analytics'])
@section('title','Institutional analytics')
@php($pageTitle = 'Institutional analytics')
@php($breadcrumbs = ['Institutional analytics'])

@section('content')
@include('components.page-header', [
  'title' => 'Institutional analytics',
  'subtitle' => 'Cross-college analysis for executive decision making.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Recommendation acceptance','value'=>'82%','icon'=>'hand-thumbs-up','tone'=>'lav','delta'=>'+5%','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Avg. match score','value'=>'85%','icon'=>'magic','tone'=>'brand','delta'=>'+2%','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Establishment utilization','value'=>'61%','icon'=>'building-check','tone'=>'gold','delta'=>'+7%','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'At-risk interns','value'=>'34','icon'=>'shield-exclamation','tone'=>'green','delta'=>'-11','deltaDir'=>'down'])</div>
</div><div class="row g-3"><div class="col-xl-6"><section class="card-x h-100">
  <div class="card-x-head"><h3>Utilization by industry</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b " style="height:92%"></div><div class="b " style="height:78%"></div><div class="b " style="height:64%"></div><div class="b " style="height:52%"></div><div class="b " style="height:44%"></div><div class="b " style="height:31%"></div></div>
    <div class="chart-x-labels"><span>Software</span><span>Gov't</span><span>BPO</span><span>Logistics</span><span>Agri</span><span>Retail</span></div>
  </div>
</section></div><div class="col-xl-6"><section class="card-x h-100">
  <div class="card-x-head"><h3>Monthly analytics</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b lav" style="height:52%"></div><div class="b lav" style="height:74%"></div><div class="b lav" style="height:88%"></div><div class="b lav" style="height:68%"></div><div class="b lav" style="height:45%"></div><div class="b lav" style="height:30%"></div></div>
    <div class="chart-x-labels"><span>Jun</span><span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span><span>Nov</span></div>
  </div>
</section></div></div>
@endsection
