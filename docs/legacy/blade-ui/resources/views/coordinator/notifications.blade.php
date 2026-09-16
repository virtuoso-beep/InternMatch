@extends('layouts.app', ['role' => 'coordinator', 'active' => 'notifications'])
@section('title','Notifications')
@php($pageTitle = 'Notifications')
@php($breadcrumbs = ['Notifications'])

@section('content')
@include('components.page-header', [
  'title' => 'Notifications',
  'subtitle' => 'System alerts for the practicum office.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<section class="card-x mb-4">
  <div class="card-x-head"><h3>Alerts</h3><span class="ms-auto fs-12 text-muted-2">4 shown</span></div>
  @include('components.table-toolbar', ['id'=>'notTbl','target'=>'#notTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="notTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-notTbl" aria-label="Select all"></th><th data-sort>Alert</th><th data-sort>Category</th><th data-sort>Reference</th><th data-sort>When</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">MOA expiring in 34 days</span></td><td><span class="badge-x badge-warning">Compliance</span></td><td>BrightPath BPO</td><td>2 hours ago</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Intern has no log for 12 days</span></td><td><span class="badge-x badge-danger">At risk</span></td><td>Paolo Villanueva</td><td>5 hours ago</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">New opportunity submitted</span></td><td><span class="badge-x badge-info">Opportunity</span></td><td>Northline Analytics</td><td>Yesterday</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Batch recommendation completed</span></td><td><span class="badge-x badge-ai">Engine</span></td><td>148 students scored</td><td>Yesterday</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>4,'total'=>28])</div>
</section>
@endsection
