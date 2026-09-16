@extends('layouts.app', ['role' => 'student', 'active' => 'resume'])
@section('title','Resume builder')
@php($pageTitle = 'Resume builder')
@php($breadcrumbs = ['Resume builder'])

@section('content')
@include('components.page-header', [
  'title' => 'Resume builder',
  'subtitle' => 'Generate a program-formatted resume from your profile and competencies.',
  'actions' => "<a href='#' class='btn btn-ghost btn-sm'><i class='bi bi-download me-1'></i>Export</a>",
])

<div class="row g-3">
  <div class="col-lg-5">
    <section class="card-x mb-3"><div class="card-x-head"><h3>Sections</h3></div><div class="card-x-body d-grid gap-2">
      @foreach([['Personal details',100],['Education',100],['Competencies',86],['Projects',70],['Certifications',40],['References',0]] as $s)
        @include('components.progress',['value'=>$s[1],'label'=>$s[0]])
      @endforeach
    </div><div class="card-x-foot"><button class="btn btn-brand btn-sm w-100" onclick="IMToast('Resume regenerated from your profile.','success')">Regenerate resume</button></div></section>
    <section class="card-x"><div class="card-x-head"><h3>Template</h3></div><div class="card-x-body d-flex flex-wrap gap-2" data-chip-group>
      <button class="chip active" type="button">UM Standard</button><button class="chip" type="button">Technical</button><button class="chip" type="button">Minimal</button>
    </div></section>
  </div>
  <div class="col-lg-7"><section class="card-x h-100"><div class="card-x-head"><h3>Preview</h3>
    <div class="ms-auto d-flex gap-2"><button class="btn btn-ghost btn-sm"><i class="bi bi-printer me-1"></i>Print</button><button class="btn btn-ghost btn-sm"><i class="bi bi-download me-1"></i>PDF</button></div></div>
    <div class="card-x-body"><div class="chart-ph" style="min-height:520px"><span><i class="bi bi-file-earmark-person fs-3 d-block mb-2"></i>Resume document preview placeholder</span></div></div>
  </section></div>
</div>
@endsection
