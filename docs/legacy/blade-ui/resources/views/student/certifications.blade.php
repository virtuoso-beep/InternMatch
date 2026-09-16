@extends('layouts.app', ['role' => 'student', 'active' => 'certifications'])
@section('title','Certifications')
@php($pageTitle = 'Certifications')
@php($breadcrumbs = ['Certifications'])

@section('content')
@include('components.page-header', [
  'title' => 'Certifications',
  'subtitle' => 'Verified training that boosts your matching score.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-plus-lg me-1'></i>Add certification</button>",
])

<section class="card-x mb-4">
  <div class="card-x-head"><h3>My certifications</h3><span class="ms-auto fs-12 text-muted-2">4 shown</span></div>
  @include('components.table-toolbar', ['id'=>'certTbl','target'=>'#certTbl','filters'=>['All','Verified','Pending'],'placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="certTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-certTbl" aria-label="Select all"></th><th data-sort>Certification</th><th data-sort>Issuer</th><th data-sort>Issued</th><th data-sort>Expiry</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Cisco Networking Essentials</span></td><td>Cisco NetAcad</td><td>Feb 2026</td><td>Feb 2029</td><td><span class="badge-x badge-success">Verified</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">AWS Cloud Practitioner</span></td><td>Amazon Web Services</td><td>Jan 2026</td><td>Jan 2029</td><td><span class="badge-x badge-info">Under review</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Data Privacy Awareness</span></td><td>NPC Philippines</td><td>Nov 2025</td><td>—</td><td><span class="badge-x badge-success">Verified</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Google IT Support</span></td><td>Coursera</td><td>Aug 2025</td><td>—</td><td><span class="badge-x badge-warning">Evidence needed</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>4,'total'=>28])</div>
</section>
@endsection
