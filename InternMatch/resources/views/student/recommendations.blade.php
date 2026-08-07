@extends('layouts.app', ['role' => 'student', 'active' => 'recommendations'])
@section('title','Recommended internships')
@php($pageTitle = 'Recommended internships')
@php($breadcrumbs = ['Recommended internships'])

@section('content')
@include('components.page-header', [
  'title' => 'Recommended internships',
  'subtitle' => 'Ranked by competency match, accessibility, and establishment capacity.',
  'actions' => "<a href='{{ route(\'student.accessibility-map\') }}' class='btn btn-ghost btn-sm'><i class='bi bi-geo-alt me-1'></i>View on map</a>",
])

<div class="d-flex flex-wrap gap-2 mb-3" data-chip-group>
  <button class="chip active" type="button">All (3)</button>
  <button class="chip" type="button">Nearby (&lt; 5 km)</button>
  <button class="chip" type="button">Highest match</button>
  <button class="chip" type="button">Slots open</button>
</div>
@include('components.alert',['type'=>'ai','title'=>'How this list was produced','message'=>'Your 9 competencies were compared semantically against 412 internship task descriptions, then reranked using accessibility and capacity. <a href="'.route('student.recommendation-explanation').'">See the full explanation</a>.'])
<div class="d-grid gap-3">
  @foreach([
    ['DC','DataCore Solutions Inc.',92,2,'Software Development','Tagum City','3.2',88,75,['Laravel','MySQL','REST APIs','Git']],
    ['TC','Tagum City IT Office',87,1,'Systems Support','Tagum City','1.8',96,45,['Networking','Helpdesk','Documentation']],
    ['NA','Northline Analytics',81,3,'Data Engineering','Mankilam','6.4',74,80,['SQL','Python','Dashboards']],
  ] as $r)
    @include('components.rec-card', ['initials'=>$r[0],'company'=>$r[1],'match'=>$r[2],'slots'=>$r[3],'track'=>$r[4],'location'=>$r[5],'distance'=>$r[6],'access'=>$r[7],'capacity'=>$r[8],'skills'=>$r[9]])
  @endforeach
</div>
@endsection
