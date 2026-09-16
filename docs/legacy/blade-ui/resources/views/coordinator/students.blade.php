@extends('layouts.app', ['role' => 'coordinator', 'active' => 'students'])
@section('title','Student management')
@php($pageTitle = 'Student management')
@php($breadcrumbs = ['Student management'])

@section('content')
@include('components.page-header', [
  'title' => 'Student management',
  'subtitle' => 'All practicum students for the current academic term.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-upload me-1'></i>Import students</button>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Total students','value'=>'540','icon'=>'people','tone'=>'brand'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Deployed','value'=>'392','icon'=>'check-circle','tone'=>'green','delta'=>'+34','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Unplaced','value'=>'148','icon'=>'exclamation-circle','tone'=>'gold'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'At risk','value'=>'9','icon'=>'shield-exclamation','tone'=>'lav'])</div>
</div><section class="card-x mb-4">
  <div class="card-x-head"><h3>Students</h3><span class="ms-auto fs-12 text-muted-2">6 shown</span></div>
  @include('components.table-toolbar', ['id'=>'stuTbl','target'=>'#stuTbl','filters'=>['All (540)','Deployed','Pending','Unplaced'],'placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="stuTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-stuTbl" aria-label="Select all"></th><th data-sort>Student</th><th data-sort>Student no.</th><th data-sort>Program</th><th data-sort>Year</th><th data-sort>Establishment</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Trisha Talamillo</span></td><td>2022-00145</td><td>BSIT</td><td>4th</td><td>DataCore Solutions Inc.</td><td><span class="badge-x badge-success">Deployed</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Mark Delos Santos</span></td><td>2022-00187</td><td>BSIT</td><td>4th</td><td>Tagum City IT Office</td><td><span class="badge-x badge-success">Deployed</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Angela Reyes</span></td><td>2022-00203</td><td>BSCS</td><td>4th</td><td>Northline Analytics</td><td><span class="badge-x badge-warning">Pending</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Jomar Bautista</span></td><td>2022-00341</td><td>BSBA</td><td>4th</td><td>BrightPath BPO</td><td><span class="badge-x badge-success">Deployed</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Karla Mendoza</span></td><td>2022-00399</td><td>BSHM</td><td>4th</td><td>—</td><td><span class="badge-x badge-info">Matching</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Paolo Villanueva</span></td><td>2022-00412</td><td>BSIT</td><td>4th</td><td>—</td><td><span class="badge-x badge-danger">Incomplete profile</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>6,'total'=>42])</div>
</section>
@endsection
