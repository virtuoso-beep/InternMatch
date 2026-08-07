@extends('layouts.app', ['role' => 'coordinator', 'active' => 'companies'])
@section('title','Host establishments')
@php($pageTitle = 'Host establishments')
@php($breadcrumbs = ['Host establishments'])

@section('content')
@include('components.page-header', [
  'title' => 'Host establishments',
  'subtitle' => 'Accredited partner companies and their capacity.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-building-add me-1'></i>Add establishment</button>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Accredited','value'=>'186','icon'=>'buildings','tone'=>'brand','delta'=>'+6','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Active MOAs','value'=>'171','icon'=>'file-earmark-ruled','tone'=>'green'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Expiring soon','value'=>'8','icon'=>'calendar-x','tone'=>'gold'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Avg. rating','value'=>'4.4','icon'=>'star','tone'=>'lav','delta'=>'+0.2','deltaDir'=>'up'])</div>
</div><section class="card-x mb-4">
  <div class="card-x-head"><h3>Establishments</h3><span class="ms-auto fs-12 text-muted-2">5 shown</span></div>
  @include('components.table-toolbar', ['id'=>'coTbl','target'=>'#coTbl','filters'=>['All','Active MOA','Expiring','Suspended'],'placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="coTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-coTbl" aria-label="Select all"></th><th data-sort>Establishment</th><th data-sort>Industry</th><th data-sort>Location</th><th data-sort>Slots</th><th data-sort>MOA expiry</th><th data-sort>Rating</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">DataCore Solutions Inc.</span></td><td>Software</td><td>Tagum City</td><td>2 of 4</td><td>May 2027</td><td>4.8</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Tagum City IT Office</span></td><td>Government</td><td>Tagum City</td><td>1 of 6</td><td>Dec 2026</td><td>4.6</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Northline Analytics</span></td><td>Data services</td><td>Mankilam</td><td>3 of 5</td><td>Mar 2027</td><td>4.5</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">BrightPath BPO</span></td><td>BPO</td><td>Tagum City</td><td>7 of 20</td><td>Aug 2026</td><td>4.1</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Anflo Industrial Estate</span></td><td>Agri-industrial</td><td>Panabo</td><td>4 of 10</td><td>Jan 2027</td><td>4.0</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>5,'total'=>35])</div>
</section>
@endsection
