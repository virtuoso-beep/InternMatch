@extends('layouts.app', ['role' => 'admin', 'active' => 'academic-year'])
@section('title','Academic year & internship periods')
@php($pageTitle = 'Academic year & internship periods')
@php($breadcrumbs = ['Academic year & internship periods'])

@section('content')
@include('components.page-header', [
  'title' => 'Academic year & internship periods',
  'subtitle' => 'Define terms and practicum windows.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3">
  <div class="col-xl-5"><section class="card-x h-100"><div class="card-x-head"><h3>Current configuration</h3></div><div class="card-x-body">
    <form data-demo class="d-grid gap-3">
      <div><label class="form-label" for="ay">Academic year</label><select class="form-select" id="ay"><option>A.Y. 2026–2027</option><option>A.Y. 2025–2026</option></select></div>
      <div><label class="form-label" for="tm">Active term</label><select class="form-select" id="tm"><option>First Semester</option><option>Second Semester</option><option>Summer</option></select></div>
      <div class="row g-2"><div class="col-6"><label class="form-label" for="ps">Practicum start</label><input type="date" class="form-control" id="ps" value="2026-06-16"></div>
      <div class="col-6"><label class="form-label" for="pe">Practicum end</label><input type="date" class="form-control" id="pe" value="2026-10-30"></div></div>
      <div><label class="form-label" for="rh">Required hours</label><input class="form-control" id="rh" value="486"></div>
      <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="lock" checked><label class="form-check-label fs-13" for="lock">Lock deployment changes after the term closes</label></div>
      <button class="btn btn-brand btn-sm">Save configuration</button>
    </form>
  </div></section></div>
  <div class="col-xl-7"><section class="card-x mb-4">
  <div class="card-x-head"><h3>Periods</h3><span class="ms-auto fs-12 text-muted-2">3 shown</span></div>
  @include('components.table-toolbar', ['id'=>'ayTbl','target'=>'#ayTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="ayTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-ayTbl" aria-label="Select all"></th><th data-sort>Academic year</th><th data-sort>Term</th><th data-sort>Practicum window</th><th data-sort>Students</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">A.Y. 2026–2027</span></td><td>First Semester</td><td>Jun 16 – Oct 30, 2026</td><td>540</td><td><span class="badge-x badge-success">Active</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">A.Y. 2025–2026</span></td><td>Second Semester</td><td>Jan 8 – May 24, 2026</td><td>512</td><td><span class="badge-x badge-neutral">Closed</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">A.Y. 2025–2026</span></td><td>First Semester</td><td>Jun 17 – Oct 31, 2025</td><td>498</td><td><span class="badge-x badge-neutral">Archived</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>3,'total'=>21])</div>
</section></div></div>
@endsection
