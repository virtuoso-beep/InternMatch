@extends('layouts.app', ['role' => 'admin', 'active' => 'users'])
@section('title','User management')
@php($pageTitle = 'User management')
@php($breadcrumbs = ['User management'])

@section('content')
@include('components.page-header', [
  'title' => 'User management',
  'subtitle' => 'Create, edit, and deactivate InternMatch accounts.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-person-plus me-1'></i>Create account</button>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Total accounts','value'=>'1,680','icon'=>'people','tone'=>'brand'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Students','value'=>'1,240','icon'=>'mortarboard','tone'=>'lav'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Staff','value'=>'254','icon'=>'person-badge','tone'=>'gold'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Deactivated','value'=>'32','icon'=>'person-dash','tone'=>'green'])</div>
</div><section class="card-x mb-4">
  <div class="card-x-head"><h3>Accounts</h3><span class="ms-auto fs-12 text-muted-2">6 shown</span></div>
  @include('components.table-toolbar', ['id'=>'usrTbl','target'=>'#usrTbl','filters'=>['All','Students','Staff','External','Deactivated'],'placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="usrTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-usrTbl" aria-label="Select all"></th><th data-sort>User</th><th data-sort>Email</th><th data-sort>Role</th><th data-sort>Department</th><th data-sort>Last active</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Trisha Talamillo</span></td><td>trisha.talamillo@umindanao.edu.ph</td><td>Student Intern</td><td>CCE</td><td>2 minutes ago</td><td><span class="badge-x badge-success">Active</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Ms. Marissa Cortez</span></td><td>m.cortez@umindanao.edu.ph</td><td>Practicum Coordinator</td><td>CCE</td><td>5 minutes ago</td><td><span class="badge-x badge-success">Active</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Mr. Rico Fernandez</span></td><td>rico@datacore.ph</td><td>Host Supervisor</td><td>External</td><td>Yesterday</td><td><span class="badge-x badge-success">Active</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Dr. Joana Delos Reyes</span></td><td>j.delosreyes@umindanao.edu.ph</td><td>Program Chair</td><td>CCE</td><td>3 hours ago</td><td><span class="badge-x badge-success">Active</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Paolo Villanueva</span></td><td>p.villanueva@umindanao.edu.ph</td><td>Student Intern</td><td>CBA</td><td>2 weeks ago</td><td><span class="badge-x badge-warning">Dormant</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Leo Ramirez</span></td><td>l.ramirez@umindanao.edu.ph</td><td>Student Intern</td><td>CBA</td><td>—</td><td><span class="badge-x badge-neutral">Deactivated</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>6,'total'=>42])</div>
</section>
@endsection
