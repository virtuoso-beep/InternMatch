@extends('layouts.app', ['role' => 'supervisor', 'active' => 'progress'])
@section('title','Progress monitoring')
@php($pageTitle = 'Progress monitoring')
@php($breadcrumbs = ['Progress monitoring'])

@section('content')
@include('components.page-header', [
  'title' => 'Progress monitoring',
  'subtitle' => 'Hours and milestones for your interns.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3 mb-3"><div class="col-xl-8"><section class="card-x h-100">
  <div class="card-x-head"><h3>Hours rendered per intern</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b alt" style="height:86%"></div><div class="b alt" style="height:64%"></div><div class="b alt" style="height:58%"></div><div class="b alt" style="height:32%"></div><div class="b alt" style="height:48%"></div><div class="b alt" style="height:20%"></div></div>
    <div class="chart-x-labels"><span>Trisha</span><span>Mark</span><span>Angela</span><span>Jomar</span><span>Karla</span><span>Paolo</span></div>
  </div>
</section></div><div class="col-xl-4"><section class="card-x h-100"><div class="card-x-head"><h3>Team completion</h3></div>
  <div class="card-x-body text-center">
    <div class="ring  mx-auto" style="--val:58"><b>58%</b></div>
    <p class="fs-13 text-muted-2 mt-3 mb-0">1,142 of 1,944 combined hours</p>
  </div></section></div></div><section class="card-x mb-4">
  <div class="card-x-head"><h3>Progress</h3><span class="ms-auto fs-12 text-muted-2">3 shown</span></div>
  @include('components.table-toolbar', ['id'=>'spTbl','target'=>'#spTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="spTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-spTbl" aria-label="Select all"></th><th data-sort>Intern</th><th data-sort>Required</th><th data-sort>Rendered</th><th data-sort>Remaining</th><th data-sort>Projected completion</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Trisha Talamillo</span></td><td>486</td><td>312</td><td>174</td><td>Oct 28, 2026</td><td><span class="badge-x badge-success">On track</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Mark Delos Santos</span></td><td>486</td><td>208</td><td>278</td><td>Nov 6, 2026</td><td><span class="badge-x badge-warning">Slightly behind</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Jomar Bautista</span></td><td>486</td><td>96</td><td>390</td><td>Dec 2, 2026</td><td><span class="badge-x badge-danger">At risk</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>3,'total'=>21])</div>
</section>
@endsection
