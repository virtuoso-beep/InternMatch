@extends('layouts.app', ['role' => 'student', 'active' => 'recommendation-explanation'])
@section('title','Why this match')
@php($pageTitle = 'Why this match')
@php($breadcrumbs = ['Why this match'])

@section('content')
@include('components.page-header', [
  'title' => 'Why this match',
  'subtitle' => 'Full reasoning behind the DataCore Solutions Inc. recommendation.',
  'actions' => "<button class='btn btn-brand btn-sm'>Express interest</button>",
])

<div class="row g-3">
  <div class="col-xl-8">
    <section class="card-x mb-3">
      <div class="card-x-head"><h3>Score breakdown</h3><span class="match-pill ms-auto">92% overall</span></div>
      <div class="card-x-body d-grid gap-3">
        @foreach([['Competency similarity',92,'lav','40% weight'],['Accessibility score',88,'green','25% weight'],['Capacity availability',75,'gold','15% weight'],['Historical acceptance',81,'','20% weight']] as $s)
          <div><div class="d-flex justify-content-between fs-12 mb-1"><span class="text-body-2">{{ $s[0] }} <span class="text-muted-2">· {{ $s[3] }}</span></span><span class="fw-semibold text-heading">{{ $s[1] }}%</span></div>
          <div class="prog {{ $s[2] }}"><span style="width:{{ $s[1] }}%"></span></div></div>
        @endforeach
      </div>
    </section>
    <section class="card-x mb-3">
      <div class="card-x-head"><h3>Matched competencies</h3><span class="ms-auto fs-12 text-muted-2">Your statement → their task</span></div>
      <div class="table-wrap"><table class="table-x">
        <thead><tr><th>Your competency</th><th>Internship task</th><th>Similarity</th></tr></thead>
        <tbody>
          @foreach([['Built a REST API using Laravel and MySQL','Develop and maintain internal API endpoints',0.94],['Designed normalized relational schemas','Assist in database schema review',0.89],['Version control with Git and GitHub','Collaborate via Git-based workflows',0.86],['Wrote technical documentation','Produce module documentation',0.71]] as $m)
            <tr><td class="cell-strong">{{ $m[0] }}</td><td>{{ $m[1] }}</td><td><span class="badge-x badge-ai">{{ $m[2] }}</span></td></tr>
          @endforeach
        </tbody></table></div>
    </section>
    <section class="card-x">
      <div class="card-x-head"><h3>Ranking against other establishments</h3></div>
      <div class="table-wrap"><table class="table-x">
        <thead><tr><th>#</th><th>Establishment</th><th data-sort>Similarity</th><th data-sort>Accessibility</th><th data-sort>Final score</th></tr></thead>
        <tbody>
          @foreach([[1,'DataCore Solutions Inc.',92,88,92],[2,'Tagum City IT Office',84,96,87],[3,'Northline Analytics',86,74,81],[4,'BrightPath BPO',71,82,74],[5,'Anflo Industrial Estate',64,58,61]] as $r)
            <tr><td>{{ $r[0] }}</td><td class="cell-strong">{{ $r[1] }}</td><td>{{ $r[2] }}%</td><td>{{ $r[3] }}%</td><td><strong class="rec-score">{{ $r[4] }}%</strong></td></tr>
          @endforeach
        </tbody></table></div>
    </section>
  </div>
  <div class="col-xl-4">
    <section class="card-x mb-3"><div class="card-x-head"><h3>Model confidence</h3></div><div class="card-x-body">
      @include('components.confidence-meter',['value'=>5,'label'=>'Very high confidence'])
      <p class="fs-13 text-muted-2 mt-3 mb-0">Based on 148 comparable placements from previous academic years with an 89% completion rate.</p>
    </div></section>
    <section class="card-x mb-3"><div class="card-x-head"><h3>Reasoning summary</h3></div><div class="card-x-body">
      <p class="fs-13">Your strongest declared competencies are in back-end development and database design, which map directly to four of the six core tasks in this internship. The site is 3.2 km from your residence with a single-ride commute, and two of four slots remain open.</p>
      @include('components.alert',['type'=>'warn','message'=>'Lower weight: no automated testing experience was detected, which appears in one listed task.'])
    </div></section>
    <section class="card-x"><div class="card-x-head"><h3>Recommendation timeline</h3></div><div class="card-x-body">
      @include('components.timeline',['items'=>[
        ['title'=>'Competencies indexed','text'=>'9 statements embedded','state'=>'done'],
        ['title'=>'Semantic comparison','text'=>'412 task descriptions scored','state'=>'done'],
        ['title'=>'Accessibility applied','text'=>'Distance and routes factored','state'=>'done'],
        ['title'=>'Ranked shortlist issued','text'=>'3 establishments proposed','state'=>'active'],
        ['title'=>'Coordinator review','text'=>'Awaiting approval','state'=>''],
      ]])
    </div></section>
  </div>
</div>
@endsection
