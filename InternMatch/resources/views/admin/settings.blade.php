@extends('layouts.app', ['role' => 'admin', 'active' => 'settings'])
@section('title','System settings')
@php($pageTitle = 'System settings')
@php($breadcrumbs = ['System settings'])

@section('content')
@include('components.page-header', [
  'title' => 'System settings',
  'subtitle' => 'Institution-wide configuration for InternMatch.',
  'actions' => "<button class='btn btn-brand btn-sm'>Save settings</button>",
])

<div class="row g-3">
  <div class="col-xl-8"><section class="card-x mb-3"><div class="card-x-head"><h3>Matching engine</h3></div><div class="card-x-body d-grid gap-3">
    @foreach([['Competency similarity weight',40],['Accessibility weight',25],['Historical acceptance weight',20],['Capacity weight',15]] as $wgt)
      <div><label class="form-label">{{ $wgt[0] }} — {{ $wgt[1] }}%</label><input type="range" class="form-range" value="{{ $wgt[1] }}" aria-label="{{ $wgt[0] }}"></div>
    @endforeach
    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="ex" checked><label class="form-check-label fs-13" for="ex">Always show recommendation explanations to students</label></div>
  </div></section>
  <section class="card-x"><div class="card-x-head"><h3>Notifications</h3></div><div class="card-x-body d-grid gap-2">
    @foreach([['Email notifications',true],['In-app notifications',true],['Weekly digest to coordinators',true],['SMS alerts for at-risk interns',false]] as $n)
      <div class="form-check form-switch"><input class="form-check-input" type="checkbox" @checked($n[1]) id="ntf{{ $loop->index }}"><label class="form-check-label fs-13" for="ntf{{ $loop->index }}">{{ $n[0] }}</label></div>
    @endforeach
  </div></section></div>
  <div class="col-xl-4"><section class="card-x mb-3"><div class="card-x-head"><h3>Institution</h3></div><div class="card-x-body d-grid gap-3">
    <div><label class="form-label" for="inm">Institution name</label><input class="form-control" id="inm" value="University of Mindanao Tagum College"></div>
    <div><label class="form-label" for="ico">Contact email</label><input class="form-control" id="ico" value="internmatch@umindanao.edu.ph"></div>
    <div><label class="form-label" for="itz">Timezone</label><select class="form-select" id="itz"><option>Asia/Manila (GMT+8)</option></select></div>
  </div></section>
  <section class="card-x"><div class="card-x-head"><h3>Data retention</h3></div><div class="card-x-body">
    @include('components.alert',['type'=>'warn','message'=>'Archived records older than 5 academic years are purged after administrator confirmation.'])
    <button class="btn btn-ghost btn-sm w-100">Review retention policy</button>
  </div></section></div>
</div>
@endsection
