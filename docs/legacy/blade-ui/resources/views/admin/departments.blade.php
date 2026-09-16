@extends('layouts.app', ['role' => 'admin', 'active' => 'departments'])
@section('title','Departments & programs')
@php($pageTitle = 'Departments & programs')
@php($breadcrumbs = ['Departments & programs'])

@section('content')
@include('components.page-header', [
  'title' => 'Departments & programs',
  'subtitle' => 'Colleges, departments, and degree programs served.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-plus-lg me-1'></i>Add department</button>",
])

<section class="card-x mb-4">
  <div class="card-x-head"><h3>Departments</h3><span class="ms-auto fs-12 text-muted-2">4 shown</span></div>
  @include('components.table-toolbar', ['id'=>'depTbl','target'=>'#depTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="depTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-depTbl" aria-label="Select all"></th><th data-sort>Department</th><th data-sort>Code</th><th data-sort>Programs</th><th data-sort>Coordinator</th><th data-sort>Students</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">College of Computing Education</span></td><td>CCE</td><td>BSIT, BSCS</td><td>Ms. Marissa Cortez</td><td>486</td><td><span class="badge-x badge-success">Active</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">College of Business Administration</span></td><td>CBA</td><td>BSBA, BSA</td><td>Mr. Allan Sy</td><td>372</td><td><span class="badge-x badge-success">Active</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">College of Hospitality Education</span></td><td>CHE</td><td>BSHM, BSTM</td><td>Ms. Rina Pado</td><td>268</td><td><span class="badge-x badge-success">Active</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">College of Teacher Education</span></td><td>CTE</td><td>BSED, BEED</td><td>Mr. Jun Alcantara</td><td>214</td><td><span class="badge-x badge-success">Active</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>4,'total'=>28])</div>
</section>
@endsection
