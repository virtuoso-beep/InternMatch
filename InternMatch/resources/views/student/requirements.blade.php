@extends('layouts.app', ['role' => 'student', 'active' => 'requirements'])
@section('title','Internship requirements')
@php($pageTitle = 'Internship requirements')
@php($breadcrumbs = ['Internship requirements'])

@section('content')
@include('components.page-header', [
  'title' => 'Internship requirements',
  'subtitle' => 'Submit and track required documents for CHED CMO No. 104 compliance.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-upload me-1'></i>Upload document</button>",
])

<div class="row g-3 mb-3">
  <div class="col-lg-8"><section class="card-x h-100"><div class="card-x-body">
    @include('components.progress',['value'=>75,'tone'=>'green','label'=>'6 of 8 requirements submitted'])
  </div></section></div>
  <div class="col-lg-4"><section class="card-x h-100"><div class="card-x-body">
    <div class="dropzone"><input type="file" class="d-none">
      <i class="bi bi-cloud-arrow-up fs-3 text-muted-2"></i>
      <p class="fs-13 mb-0 mt-2"><strong class="text-heading">Drop a document</strong> or click to browse</p>
      <p class="fs-11 text-muted-2 mb-0">PDF, JPG or PNG · max 5 MB</p>
    </div>
  </div></section></div>
</div>
<section class="card-x mb-4">
  <div class="card-x-head"><h3>Required documents</h3><span class="ms-auto fs-12 text-muted-2">8 shown</span></div>
  @include('components.table-toolbar', ['id'=>'reqTbl','target'=>'#reqTbl','filters'=>['All (8)','Approved (5)','Pending (2)','Missing (1)'],'placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="reqTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-reqTbl" aria-label="Select all"></th><th data-sort>Document</th><th data-sort>Date submitted</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Parental Consent</span></td><td>June 2, 2026</td><td><span class="badge-x badge-success">Approved</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Medical Certificate</span></td><td>—</td><td><span class="badge-x badge-warning">Pending upload</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Insurance Policy</span></td><td>June 4, 2026</td><td><span class="badge-x badge-success">Approved</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Endorsement Letter</span></td><td>June 6, 2026</td><td><span class="badge-x badge-success">Approved</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Memorandum of Agreement</span></td><td>June 10, 2026</td><td><span class="badge-x badge-info">Under review</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Training Plan</span></td><td>June 12, 2026</td><td><span class="badge-x badge-success">Approved</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Waiver &amp; Undertaking</span></td><td>June 12, 2026</td><td><span class="badge-x badge-success">Approved</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Barangay Clearance</span></td><td>—</td><td><span class="badge-x badge-danger">Missing</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>8,'total'=>56])</div>
</section>
@endsection
