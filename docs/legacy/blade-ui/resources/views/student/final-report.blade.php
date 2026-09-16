@extends('layouts.app', ['role' => 'student', 'active' => 'final-report'])
@section('title','Final report')
@php($pageTitle = 'Final report')
@php($breadcrumbs = ['Final report'])

@section('content')
@include('components.page-header', [
  'title' => 'Final report',
  'subtitle' => 'Compile and submit your terminal internship report.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="stepper mb-4">
  @foreach([['Overview','done'],['Weekly summary','done'],['Competency reflection','active'],['Supervisor sign-off',''],['Submission','']] as $i => $s)
    <div class="step {{ $s[1] }}"><span class="bubble">{{ $s[1] === 'done' ? '✓' : $loop->iteration }}</span>{{ $s[0] }}</div>
    @if(!$loop->last)<span class="bar"></span>@endif
  @endforeach
</div>
<div class="row g-3">
  <div class="col-xl-8"><section class="card-x"><div class="card-x-head"><h3>Competency reflection</h3><span class="autosave ms-auto"><i class="pulse"></i><span>Draft saved</span></span></div>
    <div class="card-x-body"><form data-autosave data-demo>
      <div class="mb-3"><label class="form-label" for="f1">Which declared competencies were strengthened?</label><textarea class="form-control" id="f1" rows="5"></textarea></div>
      <div class="mb-3"><label class="form-label" for="f2">What new competencies did you acquire?</label><textarea class="form-control" id="f2" rows="5"></textarea></div>
      <div class="mb-3"><label class="form-label">Supporting evidence</label>
        <div class="dropzone"><input type="file" class="d-none"><i class="bi bi-paperclip fs-4 text-muted-2"></i><p class="fs-13 mb-0 mt-2">Attach certificates, outputs, or photos</p></div></div>
      <div class="d-flex gap-2 justify-content-end"><button class="btn btn-ghost btn-sm" type="button">Save draft</button><button class="btn btn-brand btn-sm">Continue</button></div>
    </form></div></section>
  </div>
  <div class="col-xl-4"><section class="card-x"><div class="card-x-head"><h3>Submission checklist</h3></div><div class="card-x-body d-grid gap-2">
    @foreach([['Narrative overview',true],['Weekly report compilation',true],['Competency reflection',false],['Supervisor evaluation attached',false],['Certificate of completion',false]] as $c)
      <div class="form-check"><input class="form-check-input" type="checkbox" @checked($c[1]) id="ck{{ $loop->index }}"><label class="form-check-label fs-13" for="ck{{ $loop->index }}">{{ $c[0] }}</label></div>
    @endforeach
  </div></section></div>
</div>
@endsection
