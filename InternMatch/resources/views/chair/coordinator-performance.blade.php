@extends('layouts.app', ['role' => 'chair', 'active' => 'coordinator-performance'])
@section('title','Coordinator performance')
@php($pageTitle = 'Coordinator performance')
@php($breadcrumbs = ['Coordinator performance'])

@section('content')
@include('components.page-header', [
  'title' => 'Coordinator performance',
  'subtitle' => 'Workload and responsiveness of practicum coordinators.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<section class="card-x mb-4">
  <div class="card-x-head"><h3>Coordinators</h3><span class="ms-auto fs-12 text-muted-2">3 shown</span></div>
  @include('components.table-toolbar', ['id'=>'cordTbl','target'=>'#cordTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="cordTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-cordTbl" aria-label="Select all"></th><th data-sort>Coordinator</th><th data-sort>Students handled</th><th data-sort>Avg. time to place</th><th data-sort>Approval backlog</th><th data-sort>Placement rate</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Ms. Marissa Cortez</span></td><td>486</td><td>6.4 days</td><td>17</td><td>96%</td><td><span class="badge-x badge-success">Excellent</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Mr. Allan Sy</span></td><td>372</td><td>8.1 days</td><td>24</td><td>92%</td><td><span class="badge-x badge-success">Good</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Ms. Rina Pado</span></td><td>268</td><td>11.6 days</td><td>41</td><td>84%</td><td><span class="badge-x badge-warning">Needs support</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>3,'total'=>21])</div>
</section>
@endsection
