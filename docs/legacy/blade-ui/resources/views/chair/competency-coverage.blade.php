@extends('layouts.app', ['role' => 'chair', 'active' => 'competency-coverage'])
@section('title','Competency coverage')
@php($pageTitle = 'Competency coverage')
@php($breadcrumbs = ['Competency coverage'])

@section('content')
@include('components.page-header', [
  'title' => 'Competency coverage',
  'subtitle' => 'Program outcomes exercised by actual placements.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3 mb-3"><div class="col-xl-7"><section class="card-x h-100"><div class="card-x-head"><h3>Coverage by program outcome</h3></div><div class="card-x-body d-grid gap-3">@foreach([["PO1 — Apply computing knowledge",94],["PO2 — Analyze complex problems",88],["PO3 — Design solutions",76],["PO4 — Function in teams",82],["PO5 — Communicate effectively",64],["PO6 — Recognize professional ethics",58]] as $p)@include("components.progress",["value"=>$p[1],"tone"=>"lav","label"=>$p[0]]) @endforeach</div></section></div><div class="col-xl-5"><section class="card-x h-100"><div class="card-x-head"><h3>Overall coverage</h3></div>
  <div class="card-x-body text-center">
    <div class="ring green mx-auto" style="--val:88"><b>88%</b></div>
    <p class="fs-13 text-muted-2 mt-3 mb-0">Across 486 program students</p>
  </div></section></div></div><section class="card-x mb-4">
  <div class="card-x-head"><h3>Competency detail</h3><span class="ms-auto fs-12 text-muted-2">4 shown</span></div>
  @include('components.table-toolbar', ['id'=>'ccTbl','target'=>'#ccTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="ccTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-ccTbl" aria-label="Select all"></th><th data-sort>Competency</th><th data-sort>Declared by</th><th data-sort>Exercised in placement</th><th data-sort>Coverage gap</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Back-end development</span></td><td>312 students</td><td>286 placements</td><td>8%</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Database design</span></td><td>298 students</td><td>254 placements</td><td>15%</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Automated testing</span></td><td>86 students</td><td>41 placements</td><td>52%</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Cloud deployment</span></td><td>64 students</td><td>22 placements</td><td>66%</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>4,'total'=>28])</div>
</section>
@endsection
