@extends('layouts.app', ['role' => 'student', 'active' => 'daily-logs'])
@section('title','Daily logs')
@php($pageTitle = 'Daily logs')
@php($breadcrumbs = ['Daily logs'])

@section('content')
@include('components.page-header', [
  'title' => 'Daily logs',
  'subtitle' => 'Record daily tasks and hours rendered.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3">
  <div class="col-xl-4"><section class="card-x h-100"><div class="card-x-head"><h3>Log today</h3><span class="autosave ms-auto"><i class="pulse"></i><span>Draft saved</span></span></div>
    <div class="card-x-body"><form data-autosave data-demo>
      <div class="row g-3">
        <div class="col-6"><label class="form-label" for="ti">Time in</label><input type="time" class="form-control" id="ti" value="08:00"></div>
        <div class="col-6"><label class="form-label" for="to">Time out</label><input type="time" class="form-control" id="to" value="17:00"></div>
        <div class="col-12"><label class="form-label" for="tk">Tasks accomplished</label><textarea class="form-control" id="tk" rows="5" placeholder="Describe what you worked on…"></textarea></div>
        <div class="col-12"><button class="btn btn-brand btn-sm w-100">Submit log</button></div>
      </div>
    </form></div></section>
  </div>
  <div class="col-xl-8"><section class="card-x mb-4">
  <div class="card-x-head"><h3>Recent logs</h3><span class="ms-auto fs-12 text-muted-2">4 shown</span></div>
  @include('components.table-toolbar', ['id'=>'dlTbl','target'=>'#dlTbl','filters'=>['This week','This month','All'],'placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="dlTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-dlTbl" aria-label="Select all"></th><th data-sort>Date</th><th data-sort>Time in</th><th data-sort>Time out</th><th data-sort>Hours</th><th data-sort>Task summary</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Aug 7, 2026</span></td><td>08:00</td><td>17:00</td><td>8.0</td><td>API endpoint refactoring</td><td><span class="badge-x badge-success">Verified</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Aug 6, 2026</span></td><td>08:00</td><td>17:00</td><td>8.0</td><td>Database index optimization</td><td><span class="badge-x badge-success">Verified</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Aug 5, 2026</span></td><td>08:15</td><td>17:00</td><td>7.75</td><td>Bug triage and documentation</td><td><span class="badge-x badge-success">Verified</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Aug 4, 2026</span></td><td>08:00</td><td>12:00</td><td>4.0</td><td>Half day — client meeting</td><td><span class="badge-x badge-info">Pending</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>4,'total'=>28])</div>
</section></div>
</div>
@endsection
