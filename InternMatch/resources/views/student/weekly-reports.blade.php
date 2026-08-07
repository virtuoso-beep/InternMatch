@extends('layouts.app', ['role' => 'student', 'active' => 'weekly-reports'])
@section('title','Weekly reports')
@php($pageTitle = 'Weekly reports')
@php($breadcrumbs = ['Weekly reports'])

@section('content')
@include('components.page-header', [
  'title' => 'Weekly reports',
  'subtitle' => 'Submit and track your weekly narrative reports.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-journal-plus me-1'></i>New weekly report</button>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Reports submitted','value'=>'8','icon'=>'journal-text','tone'=>'brand'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Approved','value'=>'7','icon'=>'check-circle','tone'=>'green'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Pending review','value'=>'1','icon'=>'hourglass-split','tone'=>'gold'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Average rating','value'=>'4.6','icon'=>'star','tone'=>'lav','delta'=>'+0.3','deltaDir'=>'up'])</div>
</div><section class="card-x mb-4">
  <div class="card-x-head"><h3>Submitted reports</h3><span class="ms-auto fs-12 text-muted-2">5 shown</span></div>
  @include('components.table-toolbar', ['id'=>'wrTbl','target'=>'#wrTbl','filters'=>['All','Approved','Under review'],'placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="wrTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-wrTbl" aria-label="Select all"></th><th data-sort>Week</th><th data-sort>Period</th><th data-sort>Hours</th><th data-sort>Submitted</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Week 8</span></td><td>Aug 4 – Aug 8</td><td>40</td><td>Aug 8, 2026</td><td><span class="badge-x badge-info">Under review</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Week 7</span></td><td>Jul 28 – Aug 1</td><td>38</td><td>Aug 1, 2026</td><td><span class="badge-x badge-success">Approved</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Week 6</span></td><td>Jul 21 – Jul 25</td><td>40</td><td>Jul 25, 2026</td><td><span class="badge-x badge-success">Approved</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Week 5</span></td><td>Jul 14 – Jul 18</td><td>36</td><td>Jul 18, 2026</td><td><span class="badge-x badge-success">Approved</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Week 4</span></td><td>Jul 7 – Jul 11</td><td>40</td><td>Jul 11, 2026</td><td><span class="badge-x badge-success">Approved</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>5,'total'=>35])</div>
</section>
@endsection
