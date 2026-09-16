@extends('layouts.app', ['role' => 'supervisor', 'active' => 'interns'])
@section('title','Assigned interns')
@php($pageTitle = 'Assigned interns')
@php($breadcrumbs = ['Assigned interns'])

@section('content')
@include('components.page-header', [
  'title' => 'Assigned interns',
  'subtitle' => 'Interns currently deployed to your establishment.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<section class="card-x mb-4">
  <div class="card-x-head"><h3>Interns</h3><span class="ms-auto fs-12 text-muted-2">4 shown</span></div>
  @include('components.table-toolbar', ['id'=>'intTbl','target'=>'#intTbl','filters'=>['All','Active','Completed'],'placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="intTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-intTbl" aria-label="Select all"></th><th data-sort>Intern</th><th data-sort>Program</th><th data-sort>Coordinator</th><th data-sort>Start date</th><th data-sort>Hours</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Trisha Talamillo</span></td><td>BSIT</td><td>Ms. Marissa Cortez</td><td>Jun 16, 2026</td><td>312 / 486</td><td><span class="badge-x badge-success">Active</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Mark Delos Santos</span></td><td>BSIT</td><td>Ms. Marissa Cortez</td><td>Jun 16, 2026</td><td>208 / 486</td><td><span class="badge-x badge-success">Active</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Angela Reyes</span></td><td>BSCS</td><td>Ms. Marissa Cortez</td><td>Jun 23, 2026</td><td>188 / 486</td><td><span class="badge-x badge-success">Active</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Jomar Bautista</span></td><td>BSBA</td><td>Mr. Allan Sy</td><td>Jul 1, 2026</td><td>96 / 486</td><td><span class="badge-x badge-warning">Needs attention</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>4,'total'=>28])</div>
</section>
@endsection
