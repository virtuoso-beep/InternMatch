@extends('layouts.app', ['role' => 'dean', 'active' => 'placement-distribution'])
@section('title','Placement distribution')
@php($pageTitle = 'Placement distribution')
@php($breadcrumbs = ['Placement distribution'])

@section('content')
@include('components.page-header', [
  'title' => 'Placement distribution',
  'subtitle' => 'Geographic spread of internship placements.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<section class="card-x mb-4">
  <div class="card-x-head"><h3>Geospatial accessibility</h3><span class="fs-12 text-muted-2">Establishments plotted against student residence</span>
    <div class="ms-auto d-flex gap-2" data-chip-group>
      <button class="chip active" type="button">All</button><button class="chip" type="button">&lt; 5 km</button><button class="chip" type="button">Public transport</button>
    </div>
  </div>
  <div class="card-x-body">
    <div class="map-ph">
      <div class="map-pin" style="top:38%;left:28%"><b>1</b></div>
      <div class="map-pin gold" style="top:56%;left:52%"><b>2</b></div>
      <div class="map-pin green" style="top:30%;left:68%"><b>3</b></div>
      <div class="map-pin" style="top:70%;left:74%"><b>4</b></div>
      <span class="position-absolute bottom-0 start-0 m-3 fs-11 text-muted-2">Map provider placeholder — mount Leaflet or Google Maps here.</span>
    </div>
    <div class="row g-3 mt-1">
      @foreach([['DataCore Solutions Inc.','3.2 km','12 min · jeepney + walk',92],['Tagum City IT Office','1.8 km','8 min · single ride',96],['Northline Analytics','6.4 km','24 min · two rides',74],['Anflo Industrial Estate','11.2 km','38 min · bus',58]] as $m)
      <div class="col-md-6 col-xl-3">
        <div class="card-x hover-lift h-100"><div class="card-x-body">
          <h4 style="font-size:.9rem">{{ $m[0] }}</h4>
          <p class="fs-12 text-muted-2 mb-2">{{ $m[1] }} · {{ $m[2] }}</p>
          @include('components.progress', ['value'=>$m[3],'tone'=>'green','label'=>'Accessibility score'])
        </div></div>
      </div>
      @endforeach
    </div>
  </div>
</section>
@endsection
