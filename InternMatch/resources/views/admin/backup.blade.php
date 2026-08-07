@extends('layouts.app', ['role' => 'admin', 'active' => 'backup'])
@section('title','Backup & recovery')
@php($pageTitle = 'Backup & recovery')
@php($breadcrumbs = ['Backup & recovery'])

@section('content')
@include('components.page-header', [
  'title' => 'Backup & recovery',
  'subtitle' => 'Scheduled snapshots and restore points.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-hdd me-1'></i>Create backup now</button>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Last backup','value'=>'02:00 today','icon'=>'hdd-stack','tone'=>'green'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Retention','value'=>'30 days','icon'=>'calendar3','tone'=>'brand'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Storage used','value'=>'68%','icon'=>'database','tone'=>'gold'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Restore points','value'=>'28','icon'=>'arrow-counterclockwise','tone'=>'lav'])</div>
</div><section class="card-x mb-4">
  <div class="card-x-head"><h3>Restore points</h3><span class="ms-auto fs-12 text-muted-2">3 shown</span></div>
  @include('components.table-toolbar', ['id'=>'bkTbl','target'=>'#bkTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="bkTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-bkTbl" aria-label="Select all"></th><th data-sort>Snapshot</th><th data-sort>Type</th><th data-sort>Size</th><th data-sort>Created</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">snapshot-2026-08-07-0200</span></td><td>Automatic</td><td>4.2 GB</td><td>Today 02:00</td><td><span class="badge-x badge-success">Verified</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">snapshot-2026-08-06-0200</span></td><td>Automatic</td><td>4.1 GB</td><td>Yesterday 02:00</td><td><span class="badge-x badge-success">Verified</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">pre-migration-manual</span></td><td>Manual</td><td>4.0 GB</td><td>Aug 4, 2026</td><td><span class="badge-x badge-info">Retained</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>3,'total'=>21])</div>
</section>
@endsection
