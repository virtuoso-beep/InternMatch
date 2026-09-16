@extends('layouts.app', ['role' => 'chair', 'active' => 'dashboard'])
@section('title','Program overview')
@php($pageTitle = 'Program overview')
@php($breadcrumbs = ['Dashboard'])

@section('content')
@include('components.page-header', [
  'title' => 'Program overview',
  'subtitle' => 'BSIT and BSCS practicum performance this term.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Program students','value'=>'486','icon'=>'mortarboard','tone'=>'brand'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Deployment rate','value'=>'94%','icon'=>'briefcase','tone'=>'green','delta'=>'+4%','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Competency coverage','value'=>'88%','icon'=>'stars','tone'=>'lav','delta'=>'+6%','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Completion rate','value'=>'91%','icon'=>'patch-check','tone'=>'gold','delta'=>'+2%','deltaDir'=>'up'])</div>
</div><div class="row g-3 mb-3"><div class="col-xl-7"><section class="card-x h-100">
  <div class="card-x-head"><h3>Deployment by establishment type</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b " style="height:92%"></div><div class="b " style="height:74%"></div><div class="b " style="height:66%"></div><div class="b " style="height:58%"></div><div class="b " style="height:42%"></div><div class="b " style="height:28%"></div></div>
    <div class="chart-x-labels"><span>Software</span><span>Gov't</span><span>BPO</span><span>Analytics</span><span>Retail</span><span>Other</span></div>
  </div>
</section></div><div class="col-xl-5"><section class="card-x h-100">
  <div class="card-x-head"><h3>Competency coverage vs. program outcomes</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b lav" style="height:94%"></div><div class="b lav" style="height:88%"></div><div class="b lav" style="height:76%"></div><div class="b lav" style="height:82%"></div><div class="b lav" style="height:64%"></div><div class="b lav" style="height:58%"></div></div>
    <div class="chart-x-labels"><span>PO1</span><span>PO2</span><span>PO3</span><span>PO4</span><span>PO5</span><span>PO6</span></div>
  </div>
</section></div></div><section class="card-x mb-4">
  <div class="card-x-head"><h3>Company performance</h3><span class="ms-auto fs-12 text-muted-2">4 shown</span></div>
  @include('components.table-toolbar', ['id'=>'chTbl','target'=>'#chTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="chTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-chTbl" aria-label="Select all"></th><th data-sort>Establishment</th><th data-sort>Interns</th><th data-sort>Avg. rating</th><th data-sort>Completion</th><th data-sort>Coverage contribution</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">DataCore Solutions Inc.</span></td><td>12</td><td>4.8</td><td>100%</td><td>High</td><td><span class="badge-x badge-success">Excellent</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Tagum City IT Office</span></td><td>9</td><td>4.6</td><td>96%</td><td>High</td><td><span class="badge-x badge-success">Excellent</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Northline Analytics</span></td><td>7</td><td>4.5</td><td>92%</td><td>Medium</td><td><span class="badge-x badge-success">Good</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">BrightPath BPO</span></td><td>18</td><td>4.1</td><td>88%</td><td>Low</td><td><span class="badge-x badge-warning">Monitor</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>4,'total'=>28])</div>
</section>
@endsection
