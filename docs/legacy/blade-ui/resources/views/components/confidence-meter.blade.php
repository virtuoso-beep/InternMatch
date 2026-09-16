{{-- @include('components.confidence-meter', ['value'=>4, 'label'=>'AI confidence']) --}}
<div class="d-flex align-items-center gap-2">
  <span class="meter" role="img" aria-label="{{ $label ?? 'Confidence' }} {{ $value }} of 5">
    @for($i=1;$i<=5;$i++)<i class="{{ $i <= $value ? 'on' : '' }}"></i>@endfor
  </span>
  <span class="fs-12 text-muted-2">{{ $label ?? 'AI confidence' }}</span>
</div>
