{{-- Recommendation card --}}
<article class="rec-card">
  <div class="d-flex flex-wrap align-items-start gap-3">
    <span class="avatar lg gold">{{ $initials }}</span>
    <div class="flex-grow-1">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <h3 style="font-size:1.02rem;margin:0">{{ $company }}</h3>
        <span class="match-pill {{ $match >= 85 ? '' : ($match >= 70 ? 'mid' : 'low') }}">{{ $match }}% match</span>
        @if(($slots ?? 0) > 0)<span class="badge-x badge-success">{{ $slots }} slots open</span>@else<span class="badge-x badge-neutral">Waitlist</span>@endif
      </div>
      <p class="fs-13 text-muted-2 mb-2 mt-1">{{ $track }} · {{ $location }} · {{ $distance }} km from residence</p>
      <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach($skills as $s)<span class="chip chip-skill">{{ $s }}</span>@endforeach
      </div>
      <div class="row g-3">
        <div class="col-sm-4">@include('components.progress', ['value'=>$match,'tone'=>'lav','label'=>'Competency similarity'])</div>
        <div class="col-sm-4">@include('components.progress', ['value'=>$access,'tone'=>'green','label'=>'Accessibility score'])</div>
        <div class="col-sm-4">@include('components.progress', ['value'=>$capacity,'tone'=>'gold','label'=>'Capacity availability'])</div>
      </div>
    </div>
    <div class="d-flex flex-column gap-2 ms-auto">
      <a href="{{ route('student.recommendation-explanation') }}" class="btn btn-ghost btn-sm"><i class="bi bi-lightbulb me-1"></i>Why this match</a>
      <button class="btn btn-brand btn-sm" onclick="IMToast('Application intent recorded for {{ $company }}.','success')">Express interest</button>
    </div>
  </div>
</article>
