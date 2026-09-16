@extends('layouts.app', ['role' => 'coordinator', 'active' => 'dashboard'])
@section('title','Practicum operations')
@php($pageTitle = 'Practicum operations')
@php($breadcrumbs = ['Dashboard'])

@section('content')
@include('components.page-header', [
  'title' => 'Practicum operations',
  'subtitle' => 'Placement, approvals, and monitoring for the current term.',
  'actions' => "<a href='{{ route(\'coordinator.recommendations\') }}' class='btn btn-brand btn-sm'><i class='bi bi-magic me-1'></i>Run recommendations</a>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Students to place','value'=>'148','icon'=>'mortarboard','tone'=>'brand','delta'=>'+12','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Deployed interns','value'=>'392','icon'=>'briefcase','tone'=>'green','delta'=>'+34','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Pending approvals','value'=>'17','icon'=>'inbox','tone'=>'gold'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Active establishments','value'=>'186','icon'=>'buildings','tone'=>'lav','delta'=>'+6','deltaDir'=>'up'])</div>
</div><div class="row g-3 mb-3">
  <div class="col-xl-8"><section class="card-x h-100">
  <div class="card-x-head"><h3>Deployment by program</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b " style="height:88%"></div><div class="b " style="height:72%"></div><div class="b " style="height:64%"></div><div class="b " style="height:55%"></div><div class="b " style="height:48%"></div><div class="b " style="height:40%"></div></div>
    <div class="chart-x-labels"><span>BSIT</span><span>BSCS</span><span>BSBA</span><span>BSHM</span><span>BSED</span><span>BSCRIM</span></div>
  </div>
</section></div>
  <div class="col-xl-4"><section class="card-x h-100"><div class="card-x-head"><h3>Approval queue</h3><a href="{{ route('coordinator.approvals') }}" class="ms-auto fs-12">Open</a></div>
    <div class="card-x-body d-grid gap-2">
      @foreach([['Trisha Talamillo','DataCore Solutions',92],['Mark Delos Santos','Tagum City IT Office',87],['Angela Reyes','Northline Analytics',84],['Jomar Bautista','BrightPath BPO',78]] as $a)
        <div class="d-flex align-items-center gap-2 p-2" style="border:1px solid var(--border);border-radius:10px">
          <span class="avatar sm">{{ strtoupper(substr($a[0],0,1)).strtoupper(substr(explode(' ',$a[0])[1],0,1)) }}</span>
          <div class="flex-grow-1"><span class="d-block text-heading fs-13 fw-semibold">{{ $a[0] }}</span><span class="fs-11 text-muted-2">{{ $a[1] }}</span></div>
          <span class="match-pill {{ $a[2] >= 85 ? '' : 'mid' }}">{{ $a[2] }}%</span>
        </div>
      @endforeach
    </div>
    <div class="card-x-foot d-flex gap-2"><button class="btn btn-ghost btn-sm w-50">Review later</button><button class="btn btn-brand btn-sm w-50" onclick="IMToast('4 deployments approved.','success')">Approve all</button></div>
  </section></div>
</div>
<section class="card-x mb-4">
  <div class="card-x-head"><h3>Internship monitoring</h3><span class="ms-auto fs-12 text-muted-2">5 shown</span></div>
  @include('components.table-toolbar', ['id'=>'monTbl','target'=>'#monTbl','filters'=>['All','Deployed','Awaiting approval','Unplaced'],'placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="monTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-monTbl" aria-label="Select all"></th><th data-sort>Student</th><th data-sort>Program</th><th data-sort>Establishment</th><th data-sort>Hours</th><th data-sort>Match</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Trisha Talamillo</span></td><td>BSIT</td><td>DataCore Solutions Inc.</td><td>312 / 486</td><td><span class="match-pill">92%</span></td><td><span class="badge-x badge-success">Deployed</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Mark Delos Santos</span></td><td>BSIT</td><td>Tagum City IT Office</td><td>208 / 486</td><td><span class="match-pill">87%</span></td><td><span class="badge-x badge-success">Deployed</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Angela Reyes</span></td><td>BSCS</td><td>Northline Analytics</td><td>0 / 486</td><td><span class="match-pill mid">84%</span></td><td><span class="badge-x badge-warning">Awaiting approval</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Jomar Bautista</span></td><td>BSBA</td><td>BrightPath BPO</td><td>96 / 486</td><td><span class="match-pill mid">78%</span></td><td><span class="badge-x badge-success">Deployed</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Karla Mendoza</span></td><td>BSHM</td><td>Sunrise Cooperative</td><td>—</td><td><span class="match-pill low">61%</span></td><td><span class="badge-x badge-info">Matching</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>5,'total'=>35])</div>
</section>
@endsection
