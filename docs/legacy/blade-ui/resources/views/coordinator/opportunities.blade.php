@extends('layouts.app', ['role' => 'coordinator', 'active' => 'opportunities'])
@section('title','Internship opportunities')
@php($pageTitle = 'Internship opportunities')
@php($breadcrumbs = ['Internship opportunities'])

@section('content')
@include('components.page-header', [
  'title' => 'Internship opportunities',
  'subtitle' => 'Published roles from accredited establishments.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-plus-lg me-1'></i>Publish opportunity</button>",
])

<section class="card-x mb-4">
  <div class="card-x-head"><h3>Opportunities</h3><span class="ms-auto fs-12 text-muted-2">5 shown</span></div>
  @include('components.table-toolbar', ['id'=>'oppTbl','target'=>'#oppTbl','filters'=>['All','Open','Filled','Under review'],'placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="oppTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-oppTbl" aria-label="Select all"></th><th data-sort>Role</th><th data-sort>Establishment</th><th data-sort>Track</th><th data-sort>Slots</th><th data-sort>Posted</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Junior Web Developer Intern</span></td><td>DataCore Solutions Inc.</td><td>Software Development</td><td>2</td><td> Jun 1, 2026</td><td><span class="badge-x badge-success">Open</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Systems Support Intern</span></td><td>Tagum City IT Office</td><td>Systems Support</td><td>1</td><td>Jun 3, 2026</td><td><span class="badge-x badge-success">Open</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Data Analyst Intern</span></td><td>Northline Analytics</td><td>Data Engineering</td><td>3</td><td>Jun 5, 2026</td><td><span class="badge-x badge-success">Open</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Customer Experience Intern</span></td><td>BrightPath BPO</td><td>Business Process</td><td>7</td><td>May 28, 2026</td><td><span class="badge-x badge-info">Under review</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Logistics Assistant Intern</span></td><td>MetroLink Logistics</td><td>Operations</td><td>0</td><td>May 20, 2026</td><td><span class="badge-x badge-neutral">Filled</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>5,'total'=>35])</div>
</section>
@endsection
