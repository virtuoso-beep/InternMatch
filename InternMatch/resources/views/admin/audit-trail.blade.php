@extends('layouts.app', ['role' => 'admin', 'active' => 'audit-trail'])
@section('title','Audit trail')
@php($pageTitle = 'Audit trail')
@php($breadcrumbs = ['Audit trail'])

@section('content')
@include('components.page-header', [
  'title' => 'Audit trail',
  'subtitle' => 'Immutable record of privileged actions.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<section class="card-x mb-4">
  <div class="card-x-head"><h3>Activity log</h3><span class="ms-auto fs-12 text-muted-2">5 shown</span></div>
  @include('components.table-toolbar', ['id'=>'audTbl','target'=>'#audTbl','filters'=>['All','Administrative','Approvals','System'],'placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="audTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-audTbl" aria-label="Select all"></th><th data-sort>Timestamp</th><th data-sort>Actor</th><th data-sort>Role</th><th data-sort>Action</th><th data-sort>Module</th><th data-sort>IP</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Aug 7, 2026 09:14</span></td><td>Ms. Marissa Cortez</td><td>Coordinator</td><td>Approved deployment #4821</td><td>Approvals</td><td>10.20.4.18</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Aug 7, 2026 08:56</span></td><td>Engr. Rolando Abella</td><td>Administrator</td><td>Created account (student)</td><td>User Management</td><td>10.20.4.2</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Aug 7, 2026 02:00</span></td><td>System</td><td>—</td><td>Nightly backup completed</td><td>Backup</td><td>—</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Aug 6, 2026 16:41</span></td><td>Engr. Rolando Abella</td><td>Administrator</td><td>Updated matching weights</td><td>System Settings</td><td>10.20.4.2</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Aug 6, 2026 15:02</span></td><td>Mr. Rico Fernandez</td><td>Supervisor</td><td>Submitted evaluation</td><td>Evaluation</td><td>112.20.9.44</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>5,'total'=>35])</div>
</section>
@endsection
