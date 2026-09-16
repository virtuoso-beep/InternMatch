@extends('layouts.app', ['role' => 'student', 'active' => 'portfolio'])
@section('title','Portfolio')
@php($pageTitle = 'Portfolio')
@php($breadcrumbs = ['Portfolio'])

@section('content')
@include('components.page-header', [
  'title' => 'Portfolio',
  'subtitle' => 'Evidence that strengthens your competency scores.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-plus-lg me-1'></i>Add item</button>",
])

<div class="row g-3">
  @foreach([['Barangay Records System','Laravel · MySQL','Capstone-adjacent project with role-based access.','92'],['Inventory Dashboard','Chart.js · PHP','Stock analytics for a campus organization.','78'],['Campus Event App','JavaScript','Registration and QR attendance prototype.','66'],['Portfolio Website','HTML · CSS','Personal portfolio with responsive layout.','54']] as $p)
    <div class="col-md-6 col-xl-3"><div class="card-x hover-lift h-100">
      <div class="card-x-body">
        <div class="chart-ph mb-3" style="min-height:130px"><i class="bi bi-image fs-4"></i></div>
        <h3 style="font-size:.95rem">{{ $p[0] }}</h3>
        <p class="fs-12 text-muted-2">{{ $p[1] }}</p>
        <p class="fs-13">{{ $p[2] }}</p>
        @include('components.progress',['value'=>(int)$p[3],'tone'=>'lav','label'=>'Evidence strength'])
      </div>
    </div></div>
  @endforeach
  <div class="col-md-6 col-xl-3"><div class="card-x h-100"><div class="card-x-body d-grid" style="place-items:center">
    <div class="dropzone w-100"><input type="file" class="d-none"><i class="bi bi-plus-lg fs-4 text-muted-2"></i><p class="fs-13 mb-0 mt-2">Add portfolio item</p></div>
  </div></div></div>
</div>
@endsection
