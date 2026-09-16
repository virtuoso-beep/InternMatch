@extends('layouts.app', ['role' => 'supervisor', 'active' => 'dashboard'])
@section('title','Intern overview')
@php($pageTitle = 'Intern overview')
@php($breadcrumbs = ['Dashboard'])

@section('content')
@include('components.page-header', [
  'title' => 'Intern overview',
  'subtitle' => 'Your assigned interns at DataCore Solutions Inc.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Assigned interns','value'=>'6','icon'=>'people','tone'=>'brand'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Present today','value'=>'5','icon'=>'calendar-check','tone'=>'green'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Pending evaluations','value'=>'2','icon'=>'clipboard-check','tone'=>'gold'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Avg. performance','value'=>'4.5 / 5','icon'=>'star','tone'=>'lav','delta'=>'+0.2','deltaDir'=>'up'])</div>
</div><div class="row g-3 mb-3"><div class="col-xl-7"><section class="card-x h-100">
  <div class="card-x-head"><h3>Weekly attendance rate</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b " style="height:92%"></div><div class="b " style="height:88%"></div><div class="b " style="height:96%"></div><div class="b " style="height:94%"></div><div class="b " style="height:98%"></div></div>
    <div class="chart-x-labels"><span>W4</span><span>W5</span><span>W6</span><span>W7</span><span>W8</span></div>
  </div>
</section></div>
 <div class="col-xl-5"><section class="card-x h-100"><div class="card-x-head"><h3>Today at a glance</h3></div><div class="card-x-body d-grid gap-2">
   @foreach([['Trisha Talamillo','08:00','Present','success'],['Mark Delos Santos','08:05','Present','success'],['Angela Reyes','08:00','Present','success'],['Jomar Bautista','—','On leave','warning'],['Karla Mendoza','08:12','Present','success']] as $a)
     <div class="d-flex align-items-center gap-2"><span class="avatar sm">{{ strtoupper(substr($a[0],0,1)) }}</span>
     <span class="flex-grow-1 fs-13 text-heading">{{ $a[0] }}</span><span class="fs-12 text-muted-2">{{ $a[1] }}</span>
     @include('components.badge',['type'=>$a[3],'label'=>$a[2]])</div>
   @endforeach
 </div></section></div></div><section class="card-x mb-4">
  <div class="card-x-head"><h3>Assigned interns</h3><span class="ms-auto fs-12 text-muted-2">4 shown</span></div>
  @include('components.table-toolbar', ['id'=>'supTbl','target'=>'#supTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="supTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-supTbl" aria-label="Select all"></th><th data-sort>Intern</th><th data-sort>Program</th><th data-sort>Hours</th><th data-sort>Last log</th><th data-sort>Performance</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Trisha Talamillo</span></td><td>BSIT</td><td>312 / 486</td><td>Today</td><td>4.7</td><td><span class="badge-x badge-success">On track</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Mark Delos Santos</span></td><td>BSIT</td><td>208 / 486</td><td>Today</td><td>4.4</td><td><span class="badge-x badge-success">On track</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Angela Reyes</span></td><td>BSCS</td><td>188 / 486</td><td>Today</td><td>4.6</td><td><span class="badge-x badge-success">On track</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Jomar Bautista</span></td><td>BSBA</td><td>96 / 486</td><td>5 days ago</td><td>3.9</td><td><span class="badge-x badge-warning">Behind hours</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>4,'total'=>28])</div>
</section>
@endsection
