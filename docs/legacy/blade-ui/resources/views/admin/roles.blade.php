@extends('layouts.app', ['role' => 'admin', 'active' => 'roles'])
@section('title','Role management')
@php($pageTitle = 'Role management')
@php($breadcrumbs = ['Role management'])

@section('content')
@include('components.page-header', [
  'title' => 'Role management',
  'subtitle' => 'Permissions granted to each system role.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-plus-lg me-1'></i>New role</button>",
])

<div class="row g-3 mb-3">
  @foreach([['System Administrator',6,'shield-lock',''],['Practicum Coordinator',254,'clipboard-check','gold'],['Student Intern',1240,'mortarboard','lav'],['Host Supervisor',148,'buildings','green']] as $r)
    <div class="col-6 col-xl-3"><div class="card-x hover-lift h-100"><div class="card-x-body">
      <div class="feature-ico {{ $r[3] }}"><i class="bi bi-{{ $r[2] }}"></i></div>
      <h3 style="font-size:.95rem">{{ $r[0] }}</h3><p class="fs-13 text-muted-2 mb-0">{{ number_format($r[1]) }} accounts</p>
    </div></div></div>
  @endforeach
</div>
<section class="card-x"><div class="card-x-head"><h3>Permission matrix</h3><span class="ms-auto fs-12 text-muted-2">Changes are recorded in the audit trail</span></div>
<div class="table-wrap"><table class="table-x">
  <thead><tr><th>Capability</th><th>Admin</th><th>Coordinator</th><th>Chair</th><th>Dean</th><th>Supervisor</th><th>Student</th></tr></thead>
  <tbody>
  @foreach([
    ['Manage user accounts',[1,0,0,0,0,0]],['Approve deployments',[1,1,0,0,0,0]],['Publish opportunities',[1,1,0,0,1,0]],
    ['View institutional analytics',[1,1,1,1,0,0]],['Submit evaluations',[0,0,0,0,1,0]],['Submit reports & logs',[0,0,0,0,0,1]],
    ['Export institutional data',[1,1,1,1,0,0]],['Manage backups',[1,0,0,0,0,0]],
  ] as $p)
    <tr><td class="cell-strong">{{ $p[0] }}</td>
      @foreach($p[1] as $v)
        <td>@if($v)<i class="bi bi-check-circle-fill" style="color:var(--success)"></i>@else<i class="bi bi-dash" style="color:var(--muted)"></i>@endif</td>
      @endforeach
    </tr>
  @endforeach
  </tbody></table></div></section>
@endsection
