@extends('layouts.app', ['role' => 'dean', 'active' => 'completion-rates'])
@section('title','Completion rates')
@php($pageTitle = 'Completion rates')
@php($breadcrumbs = ['Completion rates'])

@section('content')
@include('components.page-header', [
  'title' => 'Completion rates',
  'subtitle' => 'Completion trends across colleges and terms.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3 mb-3"><div class="col-xl-7"><section class="card-x h-100">
  <div class="card-x-head"><h3>Completion by college</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b " style="height:94%"></div><div class="b " style="height:91%"></div><div class="b " style="height:88%"></div><div class="b " style="height:93%"></div><div class="b " style="height:86%"></div><div class="b " style="height:82%"></div></div>
    <div class="chart-x-labels"><span>CCE</span><span>CBA</span><span>CHE</span><span>CTE</span><span>CCJE</span><span>CAE</span></div>
  </div>
</section></div><div class="col-xl-5"><section class="card-x h-100"><div class="card-x-head"><h3>Institution-wide</h3></div>
  <div class="card-x-body text-center">
    <div class="ring green mx-auto" style="--val:91"><b>91%</b></div>
    <p class="fs-13 text-muted-2 mt-3 mb-0">Five-year high</p>
  </div></section></div></div><section class="card-x mb-4">
  <div class="card-x-head"><h3>Detail</h3><span class="ms-auto fs-12 text-muted-2">4 shown</span></div>
  @include('components.table-toolbar', ['id'=>'dcTbl','target'=>'#dcTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="dcTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-dcTbl" aria-label="Select all"></th><th data-sort>College</th><th data-sort>Term</th><th data-sort>Started</th><th data-sort>Completed</th><th data-sort>Withdrawn</th><th data-sort>Rate</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">CCE</span></td><td>First Sem 2026</td><td>456</td><td>429</td><td>7</td><td>94%</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">CBA</span></td><td>First Sem 2026</td><td>341</td><td>310</td><td>12</td><td>91%</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">CHE</span></td><td>First Sem 2026</td><td>238</td><td>209</td><td>9</td><td>88%</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">CTE</span></td><td>First Sem 2026</td><td>198</td><td>184</td><td>4</td><td>93%</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>4,'total'=>28])</div>
</section>
@endsection
