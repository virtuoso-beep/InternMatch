@extends('layouts.app', ['role' => 'supervisor', 'active' => 'evaluation'])
@section('title','Student evaluation')
@php($pageTitle = 'Student evaluation')
@php($breadcrumbs = ['Student evaluation'])

@section('content')
@include('components.page-header', [
  'title' => 'Student evaluation',
  'subtitle' => 'Structured performance assessment per intern.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3">
  <div class="col-xl-7"><section class="card-x"><div class="card-x-head"><h3>Evaluate — Trisha Talamillo</h3><span class="badge-x badge-info ms-2">Midterm</span></div>
    <div class="card-x-body"><form data-demo data-autosave>
      @foreach(['Technical competence','Quality of output','Initiative and resourcefulness','Communication skills','Punctuality and attendance','Professionalism','Teamwork'] as $c)
        <div class="mb-3 d-flex flex-wrap align-items-center gap-3">
          <label class="form-label mb-0 flex-grow-1">{{ $c }}</label>
          <div class="d-flex gap-3">
            @for($i=1;$i<=5;$i++)
              <div class="form-check"><input class="form-check-input" type="radio" name="c{{ $loop->parent->index }}" id="c{{ $loop->parent->index }}-{{ $i }}" @checked($i===4)>
              <label class="form-check-label fs-12" for="c{{ $loop->parent->index }}-{{ $i }}">{{ $i }}</label></div>
            @endfor
          </div>
        </div>
      @endforeach
      <div class="mb-3"><label class="form-label" for="cmt">Narrative comments</label><textarea class="form-control" id="cmt" rows="4" placeholder="Strengths, areas for improvement, recommendations…"></textarea></div>
      <div class="d-flex justify-content-between align-items-center"><span class="autosave"><i class="pulse"></i><span>Draft saved</span></span>
      <div class="d-flex gap-2"><button class="btn btn-ghost btn-sm" type="button">Save draft</button><button class="btn btn-brand btn-sm">Submit evaluation</button></div></div>
    </form></div></section>
  </div>
  <div class="col-xl-5"><section class="card-x h-100"><div class="card-x-head"><h3>Current rating</h3></div>
  <div class="card-x-body text-center">
    <div class="ring green mx-auto" style="--val:92"><b>92%</b></div>
    <p class="fs-13 text-muted-2 mt-3 mb-0">4.6 of 5.0 across seven criteria</p>
  </div></section><section class="card-x mb-4">
  <div class="card-x-head"><h3>Evaluation status</h3><span class="ms-auto fs-12 text-muted-2">3 shown</span></div>
  @include('components.table-toolbar', ['id'=>'evTbl','target'=>'#evTbl','placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="evTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-evTbl" aria-label="Select all"></th><th data-sort>Intern</th><th data-sort>Period</th><th data-sort>Rating</th><th data-sort>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Trisha Talamillo</span></td><td>Midterm</td><td>4.6</td><td><span class="badge-x badge-info">In progress</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Mark Delos Santos</span></td><td>Midterm</td><td>4.4</td><td><span class="badge-x badge-success">Submitted</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Angela Reyes</span></td><td>Midterm</td><td>—</td><td><span class="badge-x badge-warning">Not started</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>3,'total'=>21])</div>
</section></div></div>
@endsection
