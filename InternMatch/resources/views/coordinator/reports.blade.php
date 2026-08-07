@extends('layouts.app', ['role' => 'coordinator', 'active' => 'reports'])
@section('title','Reports')
@php($pageTitle = 'Reports')
@php($breadcrumbs = ['Reports'])

@section('content')
@include('components.page-header', [
  'title' => 'Reports',
  'subtitle' => 'Generate institutional and program-level practicum reports.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3">
  @foreach([
    ['file-earmark-bar-graph','Deployment Report','Students deployed per program and establishment.',''],
    ['clipboard-data','Completion Report','Hours rendered and completion status per student.','gold'],
    ['building-check','Establishment Utilization','Slot usage and intern outcomes per company.','lav'],
    ['stars','Competency Coverage','Program outcomes matched by actual placements.','green'],
    ['geo','Accessibility Report','Distance distribution and commute feasibility.',''],
    ['shield-check','CHED Compliance Pack','CMO No. 104 required documentation summary.','gold'],
  ] as $r)
    <div class="col-md-6 col-xl-4"><div class="card-x hover-lift h-100"><div class="card-x-body">
      <div class="feature-ico {{ $r[3] }}"><i class="bi bi-{{ $r[0] }}"></i></div>
      <h3 style="font-size:.95rem">{{ $r[1] }}</h3><p class="fs-13 text-muted-2">{{ $r[2] }}</p>
      <div class="d-flex gap-2"><button class="btn btn-brand-soft btn-sm">Generate</button><button class="btn btn-ghost btn-sm">Schedule</button></div>
    </div></div></div>
  @endforeach
</div>
@endsection
