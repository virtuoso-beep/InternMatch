@extends('layouts.app', ['role' => 'admin', 'active' => 'notifications'])
@section('title','Notification center')
@php($pageTitle = 'Notification center')
@php($breadcrumbs = ['Notification center'])

@section('content')
@include('components.page-header', [
  'title' => 'Notification center',
  'subtitle' => 'Broadcasts and system alerts.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-megaphone me-1'></i>New broadcast</button>",
])

<section class="card-x mb-4">
  <div class="card-x-head"><h3>Broadcasts</h3><span class="ms-auto fs-12 text-muted-2">3 shown</span></div>
  @include('components.table-toolbar', ['id'=>'anTbl','target'=>'#anTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="anTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-anTbl" aria-label="Select all"></th><th data-sort>Message</th><th data-sort>Audience</th><th data-sort>Channel</th><th data-sort>Sent</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Practicum orientation schedule</span></td><td>All students</td><td>Email + In-app</td><td>Aug 1, 2026</td><td><span class="badge-x badge-success">Delivered</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">MOA renewal reminder</span></td><td>Coordinators</td><td>Email</td><td>Aug 3, 2026</td><td><span class="badge-x badge-success">Delivered</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Scheduled maintenance window</span></td><td>All users</td><td>In-app</td><td>Aug 6, 2026</td><td><span class="badge-x badge-info">Scheduled</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>3,'total'=>21])</div>
</section>
@endsection
