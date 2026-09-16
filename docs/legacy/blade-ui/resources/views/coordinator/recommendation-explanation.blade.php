@extends('layouts.app', ['role' => 'coordinator', 'active' => 'recommendation-explanation'])
@section('title','Recommendation explanation')
@php($pageTitle = 'Recommendation explanation')
@php($breadcrumbs = ['Recommendation explanation'])

@section('content')
@include('components.page-header', [
  'title' => 'Recommendation explanation',
  'subtitle' => 'Evidence behind a single ranked recommendation.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3">
  <div class="col-xl-7"><section class="card-x mb-3"><div class="card-x-head"><h3>Trisha Talamillo → DataCore Solutions Inc.</h3><span class="match-pill ms-auto">92%</span></div>
    <div class="card-x-body d-grid gap-3">
      @foreach([['Competency similarity',92,'lav'],['Accessibility score',88,'green'],['Capacity availability',75,'gold'],['Historical acceptance',81,'']] as $s)
        @include('components.progress',['value'=>$s[1],'tone'=>$s[2],'label'=>$s[0]])
      @endforeach
    </div></section>
    <section class="card-x"><div class="card-x-head"><h3>Matched competency pairs</h3></div>
      <div class="table-wrap"><table class="table-x"><thead><tr><th>Student statement</th><th>Task description</th><th>Score</th></tr></thead><tbody>
        @foreach([['Built a REST API using Laravel and MySQL','Develop and maintain internal API endpoints','0.94'],['Designed normalized relational schemas','Assist in database schema review','0.89'],['Version control with Git','Collaborate via Git-based workflows','0.86']] as $m)
          <tr><td class="cell-strong">{{ $m[0] }}</td><td>{{ $m[1] }}</td><td><span class="badge-x badge-ai">{{ $m[2] }}</span></td></tr>
        @endforeach
      </tbody></table></div></section>
  </div>
  <div class="col-xl-5">
    <section class="card-x mb-3"><div class="card-x-head"><h3>Coordinator decision</h3></div><div class="card-x-body">
      <label class="form-label" for="note">Decision note (recorded in the audit trail)</label>
      <textarea class="form-control mb-3" id="note" rows="4" placeholder="Optional justification…"></textarea>
      <div class="d-flex gap-2"><button class="btn btn-brand w-50" onclick="IMToast('Deployment approved.','success')">Approve</button>
      <button class="btn btn-ghost w-50" onclick="IMToast('Recommendation returned for rematching.','warning')">Override</button></div>
    </div></section>
    <section class="card-x"><div class="card-x-head"><h3>Model confidence</h3></div><div class="card-x-body">
      @include('components.confidence-meter',['value'=>5,'label'=>'Very high confidence'])
      <p class="fs-13 text-muted-2 mt-3 mb-0">Derived from 148 comparable historical placements with an 89% completion rate.</p>
    </div></section>
  </div>
</div>
@endsection
