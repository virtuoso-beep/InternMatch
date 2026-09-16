@extends('layouts.app', ['role' => 'coordinator', 'active' => 'slots'])
@section('title','Internship slots')
@php($pageTitle = 'Internship slots')
@php($breadcrumbs = ['Internship slots'])

@section('content')
@include('components.page-header', [
  'title' => 'Internship slots',
  'subtitle' => 'Capacity allocation across establishments and programs.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Total slots','value'=>'642','icon'=>'grid-3x3-gap','tone'=>'brand'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Reserved','value'=>'392','icon'=>'bookmark-check','tone'=>'green'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Available','value'=>'236','icon'=>'door-open','tone'=>'gold'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Overbooked','value'=>'14','icon'=>'exclamation-triangle','tone'=>'lav'])</div>
</div><div class="row g-3 mb-3"><div class="col-xl-7"><section class="card-x h-100">
  <div class="card-x-head"><h3>Slot utilization by industry</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b alt" style="height:92%"></div><div class="b alt" style="height:78%"></div><div class="b alt" style="height:64%"></div><div class="b alt" style="height:52%"></div><div class="b alt" style="height:44%"></div><div class="b alt" style="height:31%"></div></div>
    <div class="chart-x-labels"><span>Software</span><span>Gov't</span><span>BPO</span><span>Logistics</span><span>Agri</span><span>Retail</span></div>
  </div>
</section></div><div class="col-xl-5"><section class="card-x h-100"><div class="card-x-head"><h3>Overall utilization</h3></div>
  <div class="card-x-body text-center">
    <div class="ring gold mx-auto" style="--val:61"><b>61%</b></div>
    <p class="fs-13 text-muted-2 mt-3 mb-0">392 of 642 slots reserved</p>
  </div></section></div></div><section class="card-x mb-4">
  <div class="card-x-head"><h3>Slot allocation</h3><span class="ms-auto fs-12 text-muted-2">4 shown</span></div>
  @include('components.table-toolbar', ['id'=>'slotTbl','target'=>'#slotTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="slotTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-slotTbl" aria-label="Select all"></th><th data-sort>Establishment</th><th data-sort>Program</th><th data-sort>Allocated</th><th data-sort>Reserved</th><th data-sort>Available</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">DataCore Solutions Inc.</span></td><td>BSIT</td><td>4</td><td>2</td><td>2</td><td><span class="badge-x badge-success">Healthy</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Tagum City IT Office</span></td><td>BSIT / BSCS</td><td>6</td><td>5</td><td>1</td><td><span class="badge-x badge-warning">Nearly full</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">BrightPath BPO</span></td><td>BSBA</td><td>20</td><td>13</td><td>7</td><td><span class="badge-x badge-success">Healthy</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Sunrise Cooperative</span></td><td>BSHM</td><td>5</td><td>5</td><td>0</td><td><span class="badge-x badge-danger">Full</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>4,'total'=>28])</div>
</section>
@endsection
