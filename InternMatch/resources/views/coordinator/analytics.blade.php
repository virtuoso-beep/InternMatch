@extends('layouts.app', ['role' => 'coordinator', 'active' => 'analytics'])
@section('title','Analytics')
@php($pageTitle = 'Analytics')
@php($breadcrumbs = ['Analytics'])

@section('content')
@include('components.page-header', [
  'title' => 'Analytics',
  'subtitle' => 'Placement performance for the current academic year.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Placement rate','value'=>'96%','icon'=>'graph-up-arrow','tone'=>'green','delta'=>'+3%','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Recommendation acceptance','value'=>'82%','icon'=>'hand-thumbs-up','tone'=>'lav','delta'=>'+5%','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Avg. time to place','value'=>'6.4 days','icon'=>'stopwatch','tone'=>'gold','delta'=>'-1.2','deltaDir'=>'down'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Completion rate','value'=>'91%','icon'=>'patch-check','tone'=>'brand','delta'=>'+2%','deltaDir'=>'up'])</div>
</div><div class="row g-3 mb-3"><div class="col-xl-8"><section class="card-x h-100">
  <div class="card-x-head"><h3>Monthly deployments</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b " style="height:52%"></div><div class="b " style="height:74%"></div><div class="b " style="height:88%"></div><div class="b " style="height:68%"></div><div class="b " style="height:45%"></div><div class="b " style="height:30%"></div></div>
    <div class="chart-x-labels"><span>Jun</span><span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span><span>Nov</span></div>
  </div>
</section></div><div class="col-xl-4"><section class="card-x h-100"><div class="card-x-head"><h3>Recommendation acceptance</h3></div>
  <div class="card-x-body text-center">
    <div class="ring green mx-auto" style="--val:82"><b>82%</b></div>
    <p class="fs-13 text-muted-2 mt-3 mb-0">1,024 of 1,249 recommendations accepted</p>
  </div></section></div></div><div class="row g-3"><div class="col-xl-6"><section class="card-x h-100">
  <div class="card-x-head"><h3>Competency coverage by program</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b lav" style="height:92%"></div><div class="b lav" style="height:86%"></div><div class="b lav" style="height:74%"></div><div class="b lav" style="height:66%"></div><div class="b lav" style="height:58%"></div></div>
    <div class="chart-x-labels"><span>BSIT</span><span>BSCS</span><span>BSBA</span><span>BSHM</span><span>BSED</span></div>
  </div>
</section></div><div class="col-xl-6"><section class="card-x h-100">
  <div class="card-x-head"><h3>Distance distribution (km)</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b alt" style="height:38%"></div><div class="b alt" style="height:72%"></div><div class="b alt" style="height:54%"></div><div class="b alt" style="height:28%"></div><div class="b alt" style="height:12%"></div></div>
    <div class="chart-x-labels"><span>0-2</span><span>2-5</span><span>5-10</span><span>10-20</span><span>20+</span></div>
  </div>
</section></div></div>
@endsection
