@extends('layouts.app', ['role' => 'dean', 'active' => 'dashboard'])
@section('title','Executive dashboard')
@php($pageTitle = 'Executive dashboard')
@php($breadcrumbs = ['Executive dashboard'])

@section('content')
@include('components.page-header', [
  'title' => 'Executive dashboard',
  'subtitle' => 'College-wide practicum performance at a glance.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Total interns','value'=>'1,240','icon'=>'people','tone'=>'brand','delta'=>'+86','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Placement rate','value'=>'96%','icon'=>'graph-up-arrow','tone'=>'green','delta'=>'+3%','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Completion rate','value'=>'91%','icon'=>'patch-check','tone'=>'gold','delta'=>'+2%','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Partner establishments','value'=>'186','icon'=>'buildings','tone'=>'lav','delta'=>'+6','deltaDir'=>'up'])</div>
</div><div class="row g-3 mb-3"><div class="col-xl-8"><section class="card-x h-100">
  <div class="card-x-head"><h3>Deployment by college</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b " style="height:94%"></div><div class="b " style="height:86%"></div><div class="b " style="height:78%"></div><div class="b " style="height:70%"></div><div class="b " style="height:58%"></div><div class="b " style="height:44%"></div></div>
    <div class="chart-x-labels"><span>CCE</span><span>CBA</span><span>CHE</span><span>CTE</span><span>CCJE</span><span>CAE</span></div>
  </div>
</section></div><div class="col-xl-4"><section class="card-x h-100"><div class="card-x-head"><h3>Institutional completion</h3></div>
  <div class="card-x-body text-center">
    <div class="ring green mx-auto" style="--val:91"><b>91%</b></div>
    <p class="fs-13 text-muted-2 mt-3 mb-0">1,128 of 1,240 interns on track</p>
  </div></section></div></div><div class="row g-3"><div class="col-xl-6"><section class="card-x h-100">
  <div class="card-x-head"><h3>Placement trend (5 years)</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b alt" style="height:64%"></div><div class="b alt" style="height:72%"></div><div class="b alt" style="height:81%"></div><div class="b alt" style="height:88%"></div><div class="b alt" style="height:96%"></div></div>
    <div class="chart-x-labels"><span>2022</span><span>2023</span><span>2024</span><span>2025</span><span>2026</span></div>
  </div>
</section></div><div class="col-xl-6"><section class="card-x h-100">
  <div class="card-x-head"><h3>Accessibility score distribution</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b lav" style="height:12%"></div><div class="b lav" style="height:28%"></div><div class="b lav" style="height:54%"></div><div class="b lav" style="height:78%"></div><div class="b lav" style="height:42%"></div></div>
    <div class="chart-x-labels"><span><50</span><span>50-69</span><span>70-84</span><span>85-94</span><span>95+</span></div>
  </div>
</section></div></div>
@endsection
