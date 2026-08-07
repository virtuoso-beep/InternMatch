{{-- @include('components.stat-card', ['label'=>'','value'=>'','icon'=>'people','tone'=>'brand','delta'=>'+8.2%','deltaDir'=>'up','hint'=>'vs last term']) --}}
@php $tone = $tone ?? 'brand'; $toneClass = ['brand'=>'','gold'=>'gold','lav'=>'lav','green'=>'green'][$tone] ?? ''; @endphp
<div class="stat-card hover-lift h-100">
  <div class="d-flex align-items-start justify-content-between mb-3">
    <span class="stat-ico {{ $toneClass }}"><i class="bi bi-{{ $icon ?? 'graph-up' }}"></i></span>
    @isset($delta)<span class="stat-delta {{ $deltaDir ?? 'up' }}"><i class="bi bi-arrow-{{ ($deltaDir ?? 'up') === 'up' ? 'up' : 'down' }}-right"></i> {{ $delta }}</span>@endisset
  </div>
  <div class="stat-value" @isset($count) data-count="{{ $count }}" @endisset>{{ $value }}</div>
  <div class="stat-label">{{ $label }}</div>
  @isset($hint)<div class="fs-11 text-muted-2 mt-2">{{ $hint }}</div>@endisset
</div>
