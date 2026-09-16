@extends('layouts.app', ['role' => 'coordinator', 'active' => 'recommendations'])
@section('title','AI recommendations')
@php($pageTitle = 'AI recommendations')
@php($breadcrumbs = ['AI recommendations'])

@section('content')
@include('components.page-header', [
  'title' => 'AI recommendations',
  'subtitle' => 'Model-ranked placements awaiting your review.',
  'actions' => "<button class='btn btn-brand btn-sm'><i class='bi bi-cpu me-1'></i>Re-run matching</button>",
])

@include('components.alert',['type'=>'ai','title'=>'Batch recommendation completed','message'=>'148 unplaced students were scored against 412 opportunities in 9.4 seconds. Review the ranked shortlist below — no deployment happens without your approval.'])
<section class="card-x mb-4">
  <div class="card-x-head"><h3>Ranked recommendations</h3><span class="ms-auto fs-12 text-muted-2">5 shown</span></div>
  @include('components.table-toolbar', ['id'=>'recTbl','target'=>'#recTbl','filters'=>['All','Very high','High','Needs review'],'placeholder'=>'Search…'])
  <div class="table-wrap">
    <table class="table-x" id="recTbl">
      <thead><tr><th style="width:38px"><input class="form-check-input" type="checkbox" data-check-all="#bulk-recTbl" aria-label="Select all"></th><th data-sort>Student</th><th data-sort>Program</th><th data-sort>Recommended establishment</th><th data-sort>Similarity</th><th data-sort>Accessibility</th><th data-sort>Final score</th><th data-sort>Confidence</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <tr><td><span class="cell-strong">Trisha Talamillo</span></td><td>BSIT</td><td>DataCore Solutions Inc.</td><td>92%</td><td>88%</td><td><strong class="rec-score">92%</strong></td><td><span class="badge-x badge-ai">Very high</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Mark Delos Santos</span></td><td>BSIT</td><td>Tagum City IT Office</td><td>84%</td><td>96%</td><td><strong class="rec-score">87%</strong></td><td><span class="badge-x badge-ai">High</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Angela Reyes</span></td><td>BSCS</td><td>Northline Analytics</td><td>86%</td><td>74%</td><td><strong class="rec-score">84%</strong></td><td><span class="badge-x badge-ai">High</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Jomar Bautista</span></td><td>BSBA</td><td>BrightPath BPO</td><td>71%</td><td>82%</td><td><strong class="rec-score">78%</strong></td><td><span class="badge-x badge-ai">Moderate</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      <tr><td><span class="cell-strong">Karla Mendoza</span></td><td>BSHM</td><td>Sunrise Cooperative</td><td>58%</td><td>69%</td><td><strong class="rec-score">61%</strong></td><td><span class="badge-x badge-ai">Low</span></td><td><div class="dropdown"><button class="icon-btn" data-bs-toggle="dropdown" aria-label="Row actions"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#">View details</a></li><li><a class="dropdown-item" href="#">Edit</a></li><li><a class="dropdown-item" href="#">Export record</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item danger" href="#">Archive</a></li></ul></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="card-x-foot">@include('components.pagination', ['from'=>1,'to'=>5,'total'=>35])</div>
</section>
@endsection
