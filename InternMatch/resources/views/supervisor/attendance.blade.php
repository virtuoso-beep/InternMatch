@extends('layouts.app', ['role' => 'supervisor', 'active' => 'attendance'])
@section('title','Attendance')
@php($pageTitle = 'Attendance')
@php($breadcrumbs = ['Attendance'])

@section('content')
@include('components.page-header', [
  'title' => 'Attendance',
  'subtitle' => 'Daily time records for your interns.',
  'actions' => "<button class='btn btn-brand btn-sm'>Verify all</button>",
])

<div class="d-flex flex-wrap gap-2 mb-3" data-chip-group>
  <button class="chip active" type="button">Today</button><button class="chip" type="button">This week</button><button class="chip" type="button">This month</button>
</div>
<section class="card-x mb-4">
  <div class="card-x-head"><h3>Daily time record</h3><span class="ms-auto fs-12 text-muted-2">4 shown</span></div>
  @include('components.table-toolbar', ['id'=>'attTbl','target'=>'#attTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="attTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-attTbl" aria-label="Select all"></th><th data-sort>Intern</th><th data-sort>Date</th><th data-sort>Time in</th><th data-sort>Time out</th><th data-sort>Hours</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Trisha Talamillo</span></td><td>Aug 7, 2026</td><td>08:00</td><td>17:00</td><td>8.0</td><td><span class="badge-x badge-success">Verified</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Mark Delos Santos</span></td><td>Aug 7, 2026</td><td>08:05</td><td>17:00</td><td>7.9</td><td><span class="badge-x badge-success">Verified</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Angela Reyes</span></td><td>Aug 7, 2026</td><td>08:00</td><td>17:00</td><td>8.0</td><td><span class="badge-x badge-info">Awaiting verification</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Jomar Bautista</span></td><td>Aug 7, 2026</td><td>—</td><td>—</td><td>0.0</td><td><span class="badge-x badge-warning">On leave</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>4,'total'=>28])</div>
</section>
@endsection
