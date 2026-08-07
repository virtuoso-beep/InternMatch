@extends('layouts.app', ['role' => 'supervisor', 'active' => 'opportunities'])
@section('title','Internship opportunities')
@php($pageTitle = 'Internship opportunities')
@php($breadcrumbs = ['Internship opportunities'])

@section('content')
@include('components.page-header', [
  'title' => 'Internship opportunities',
  'subtitle' => 'Roles your establishment offers this term.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-plus-lg me-1'></i>Post opportunity</button>",
])

<section class="card-x mb-4">
  <div class="card-x-head"><h3>Opportunities</h3><span class="ms-auto fs-12 text-muted-2">3 shown</span></div>
  @include('components.table-toolbar', ['id'=>'sopTbl','target'=>'#sopTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="sopTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-sopTbl" aria-label="Select all"></th><th data-sort>Role</th><th data-sort>Track</th><th data-sort>Slots</th><th data-sort>Filled</th><th data-sort>Posted</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Junior Web Developer Intern</span></td><td>Software Development</td><td>4</td><td>2</td><td>Jun 1, 2026</td><td><span class="badge-x badge-success">Open</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">QA Testing Intern</span></td><td>Quality Assurance</td><td>2</td><td>2</td><td>Jun 1, 2026</td><td><span class="badge-x badge-neutral">Filled</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Technical Writer Intern</span></td><td>Documentation</td><td>1</td><td>0</td><td>Jul 4, 2026</td><td><span class="badge-x badge-info">Under review</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>3,'total'=>21])</div>
</section>
@endsection
