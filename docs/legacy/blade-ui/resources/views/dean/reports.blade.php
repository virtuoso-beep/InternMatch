@extends('layouts.app', ['role' => 'dean', 'active' => 'reports'])
@section('title','Executive reports')
@php($pageTitle = 'Executive reports')
@php($breadcrumbs = ['Executive reports'])

@section('content')
@include('components.page-header', [
  'title' => 'Executive reports',
  'subtitle' => 'Board- and accreditation-ready reporting.',
  'actions' => "<button class='btn btn-brand btn-sm'>Generate report</button>",
])

<section class="card-x mb-4">
  <div class="card-x-head"><h3>Reports</h3><span class="ms-auto fs-12 text-muted-2">3 shown</span></div>
  @include('components.table-toolbar', ['id'=>'drTbl','target'=>'#drTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="drTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-drTbl" aria-label="Select all"></th><th data-sort>Report</th><th data-sort>Scope</th><th data-sort>Period</th><th data-sort>Prepared by</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Annual Practicum Report</span></td><td>College-wide</td><td>A.Y. 2026–2027</td><td>Office of the Dean</td><td><span class="badge-x badge-success">Ready</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Industry Partnership Review</span></td><td>186 establishments</td><td>A.Y. 2026–2027</td><td>Practicum Office</td><td><span class="badge-x badge-success">Ready</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Accessibility & Equity Report</span></td><td>All programs</td><td>First Semester</td><td>System</td><td><span class="badge-x badge-info">Generating</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>3,'total'=>21])</div>
</section>
@endsection
