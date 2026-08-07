@extends('layouts.app', ['role' => 'coordinator', 'active' => 'moa'])
@section('title','MOA management')
@php($pageTitle = 'MOA management')
@php($breadcrumbs = ['MOA management'])

@section('content')
@include('components.page-header', [
  'title' => 'MOA management',
  'subtitle' => 'Memoranda of agreement and their validity periods.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-file-earmark-plus me-1'></i>New MOA</button>",
])

<section class="card-x mb-4">
  <div class="card-x-head"><h3>Agreements</h3><span class="ms-auto fs-12 text-muted-2">4 shown</span></div>
  @include('components.table-toolbar', ['id'=>'moaTbl','target'=>'#moaTbl','filters'=>['All','Active','Expiring','Expired'],'placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="moaTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-moaTbl" aria-label="Select all"></th><th data-sort>Establishment</th><th data-sort>MOA no.</th><th data-sort>Signed</th><th data-sort>Expiry</th><th data-sort>Coverage</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">DataCore Solutions Inc.</span></td><td>MOA-2026-014</td><td>May 2, 2026</td><td>May 1, 2027</td><td>BSIT, BSCS</td><td><span class="badge-x badge-success">Active</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Tagum City IT Office</span></td><td>MOA-2025-112</td><td>Dec 4, 2025</td><td>Dec 3, 2026</td><td>All programs</td><td><span class="badge-x badge-success">Active</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">BrightPath BPO</span></td><td>MOA-2025-088</td><td>Aug 12, 2025</td><td>Aug 11, 2026</td><td>BSBA</td><td><span class="badge-x badge-warning">Expiring in 34 days</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">GreenHarvest Corp</span></td><td>MOA-2024-201</td><td>Sep 1, 2024</td><td>Aug 31, 2025</td><td>BSA</td><td><span class="badge-x badge-danger">Expired</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>4,'total'=>28])</div>
</section>
@endsection
