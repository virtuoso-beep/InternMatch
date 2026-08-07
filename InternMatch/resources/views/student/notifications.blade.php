@extends('layouts.app', ['role' => 'student', 'active' => 'notifications'])
@section('title','Notifications')
@php($pageTitle = 'Notifications')
@php($breadcrumbs = ['Notifications'])

@section('content')
@include('components.page-header', [
  'title' => 'Notifications',
  'subtitle' => 'Everything the system has sent you.',
  'actions' => "<button class='btn btn-ghost btn-sm'>Mark all as read</button>",
])

<div class="d-flex flex-wrap gap-2 mb-3" data-chip-group>
  <button class="chip active" type="button">All</button><button class="chip" type="button">Unread</button><button class="chip" type="button">Recommendations</button><button class="chip" type="button">Requirements</button>
</div>
<section class="card-x"><div class="card-x-body p-0">
  @foreach([
    ['magic','badge-ai','A new 94% match was found','Northline Analytics opened a new Data Engineering slot.','2 minutes ago',true],
    ['check-circle','badge-success','Weekly Report #08 approved','Your supervisor approved the report with a 4.7 rating.','1 hour ago',true],
    ['exclamation-triangle','badge-warning','Medical certificate still pending','Upload before August 15 to remain compliant.','Yesterday',false],
    ['calendar-event','badge-info','Midterm evaluation opens Monday','Your supervisor will submit the evaluation form.','2 days ago',false],
    ['file-earmark-check','badge-success','MOA approved by the coordinator','Your deployment record is now active.','5 days ago',false],
  ] as $n)
    <div class="d-flex gap-3 align-items-start p-3 border-bottom" style="{{ $n[5] ? 'background:var(--surface-2)' : '' }}">
      <span class="badge-x {{ $n[1] }}"><i class="bi bi-{{ $n[0] }}"></i></span>
      <div class="flex-grow-1"><strong class="d-block text-heading fs-13">{{ $n[2] }}</strong><span class="fs-13 text-muted-2">{{ $n[3] }}</span></div>
      <span class="fs-11 text-muted-2 text-nowrap">{{ $n[4] }}</span>
    </div>
  @endforeach
</div></section>
@endsection
