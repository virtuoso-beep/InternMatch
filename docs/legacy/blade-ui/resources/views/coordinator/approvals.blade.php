@extends('layouts.app', ['role' => 'coordinator', 'active' => 'approvals'])
@section('title','Pending approvals')
@php($pageTitle = 'Pending approvals')
@php($breadcrumbs = ['Pending approvals'])

@section('content')
@include('components.page-header', [
  'title' => 'Pending approvals',
  'subtitle' => 'Deployment, requirement, and report approvals awaiting action.',
  'actions' => "<button class='btn btn-brand btn-sm'>Bulk approve</button>",
])

<section class="card-x mb-4">
  <div class="card-x-head"><h3>Approval queue</h3><span class="ms-auto fs-12 text-muted-2">4 shown</span></div>
  @include('components.table-toolbar', ['id'=>'apprTbl','target'=>'#apprTbl','filters'=>['All (17)','Deployments','Requirements','Reports'],'placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="apprTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-apprTbl" aria-label="Select all"></th><th data-sort>Type</th><th data-sort>Subject</th><th data-sort>Student / Establishment</th><th data-sort>Submitted</th><th data-sort>Priority</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="badge-x badge-brand">Deployment</span></td><td><span class="cell-strong">DataCore Solutions Inc.</span></td><td>Trisha Talamillo</td><td>Aug 6, 2026</td><td><span class="badge-x badge-danger">High</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="badge-x badge-info">Requirement</span></td><td><span class="cell-strong">Medical certificate</span></td><td>Angela Reyes</td><td>Aug 6, 2026</td><td><span class="badge-x badge-warning">Medium</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="badge-x badge-brand">Deployment</span></td><td><span class="cell-strong">Tagum City IT Office</span></td><td>Mark Delos Santos</td><td>Aug 5, 2026</td><td><span class="badge-x badge-danger">High</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="badge-x badge-neutral">Report</span></td><td><span class="cell-strong">Weekly Report #08</span></td><td>Jomar Bautista</td><td>Aug 5, 2026</td><td><span class="badge-x badge-neutral">Low</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>4,'total'=>28])</div>
</section>
@endsection
