@extends('layouts.app', ['role' => 'coordinator', 'active' => 'monitoring'])
@section('title','Internship monitoring')
@php($pageTitle = 'Internship monitoring')
@php($breadcrumbs = ['Internship monitoring'])

@section('content')
@include('components.page-header', [
  'title' => 'Internship monitoring',
  'subtitle' => 'Live status of every deployed intern.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Deployed','value'=>'392','icon'=>'briefcase','tone'=>'brand'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'On track','value'=>'364','icon'=>'check-circle','tone'=>'green'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Behind hours','value'=>'19','icon'=>'clock-history','tone'=>'gold'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'At risk','value'=>'9','icon'=>'shield-exclamation','tone'=>'lav'])</div>
</div><section class="card-x mb-4">
  <div class="card-x-head"><h3>Monitoring</h3><span class="ms-auto fs-12 text-muted-2">4 shown</span></div>
  @include('components.table-toolbar', ['id'=>'monTbl2','target'=>'#monTbl2','filters'=>['All','On track','Behind','At risk'],'placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="monTbl2">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-monTbl2" aria-label="Select all"></th><th data-sort>Student</th><th data-sort>Establishment</th><th data-sort>Supervisor</th><th data-sort>Hours</th><th data-sort>Last log</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Trisha Talamillo</span></td><td>DataCore Solutions Inc.</td><td>Mr. Rico Fernandez</td><td>312 / 486</td><td>Today</td><td><span class="badge-x badge-success">On track</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Mark Delos Santos</span></td><td>Tagum City IT Office</td><td>Ms. Lea Ombao</td><td>208 / 486</td><td>Yesterday</td><td><span class="badge-x badge-success">On track</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Jomar Bautista</span></td><td>BrightPath BPO</td><td>Mr. Dan Uy</td><td>96 / 486</td><td>5 days ago</td><td><span class="badge-x badge-warning">Behind hours</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Paolo Villanueva</span></td><td>MetroLink Logistics</td><td>Ms. Cha Lim</td><td>48 / 486</td><td>12 days ago</td><td><span class="badge-x badge-danger">At risk</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>4,'total'=>28])</div>
</section>
@endsection
