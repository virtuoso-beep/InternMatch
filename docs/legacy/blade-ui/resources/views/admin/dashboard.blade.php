@extends('layouts.app', ['role' => 'admin', 'active' => 'dashboard'])
@section('title','System overview')
@php($pageTitle = 'System overview')
@php($breadcrumbs = ['Dashboard'])

@section('content')
@include('components.page-header', [
  'title' => 'System overview',
  'subtitle' => 'Platform health, accounts, and institutional activity.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Active users','value'=>'1,680','icon'=>'people','tone'=>'brand','delta'=>'+42','deltaDir'=>'up'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Sessions today','value'=>'428','icon'=>'activity','tone'=>'lav'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Pending accounts','value'=>'23','icon'=>'person-plus','tone'=>'gold'])</div>
  <div class="col-6 col-xl-3">@include('components.stat-card', ['label'=>'Failed logins (24h)','value'=>'6','icon'=>'shield-exclamation','tone'=>'green','delta'=>'-4','deltaDir'=>'down'])</div>
</div><div class="row g-3 mb-3"><div class="col-xl-8"><section class="card-x h-100">
  <div class="card-x-head"><h3>Daily active users</h3>
    <div class="ms-auto dropdown"><button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown">This term</button>
    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">This term</a></li><li><a class="dropdown-item" href="#">Last term</a></li><li><a class="dropdown-item" href="#">Academic year</a></li></ul></div>
  </div>
  <div class="card-x-body">
    <div class="chart-bars"><div class="b " style="height:72%"></div><div class="b " style="height:88%"></div><div class="b " style="height:94%"></div><div class="b " style="height:86%"></div><div class="b " style="height:91%"></div><div class="b " style="height:42%"></div><div class="b " style="height:28%"></div></div>
    <div class="chart-x-labels"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div>
  </div>
</section></div>
 <div class="col-xl-4"><section class="card-x h-100"><div class="card-x-head"><h3>System status</h3></div><div class="card-x-body d-grid gap-3">
   @foreach([['Application','Operational','success'],['Recommendation engine','Operational','success'],['Scheduled backups','Last run 02:00','success'],['Storage usage','68% of 500 GB','warning'],['Email delivery','Degraded','warning']] as $s)
     <div class="d-flex justify-content-between align-items-center"><div><span class="d-block text-heading fs-13 fw-semibold">{{ $s[0] }}</span><span class="fs-11 text-muted-2">{{ $s[1] }}</span></div>
     @include('components.badge',['type'=>$s[2],'label'=>$s[2]==='success'?'Healthy':'Attention'])</div>
   @endforeach
 </div></section></div></div><section class="card-x mb-4">
  <div class="card-x-head"><h3>Recent activity</h3><span class="ms-auto fs-12 text-muted-2">4 shown</span></div>
  @include('components.table-toolbar', ['id'=>'actTbl','target'=>'#actTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="actTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-actTbl" aria-label="Select all"></th><th data-sort>Actor</th><th data-sort>Action</th><th data-sort>Module</th><th data-sort>IP address</th><th data-sort>When</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Ms. Marissa Cortez</span></td><td>Approved deployment</td><td>Approvals</td><td>10.20.4.18</td><td>2 minutes ago</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Engr. Rolando Abella</span></td><td>Created user account</td><td>User Management</td><td>10.20.4.2</td><td>18 minutes ago</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">System</span></td><td>Nightly backup completed</td><td>Backup</td><td>—</td><td>Today 02:00</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Mr. Rico Fernandez</span></td><td>Submitted evaluation</td><td>Evaluation</td><td>112.20.9.44</td><td>Yesterday</td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>4,'total'=>28])</div>
</section>
@endsection
