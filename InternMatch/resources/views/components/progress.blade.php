{{-- @include('components.progress', ['value'=>72,'tone'=>'','label'=>'']) --}}
<div>
  @isset($label)
    <div class="d-flex justify-content-between fs-12 mb-1"><span class="text-body-2">{{ $label }}</span><span class="text-heading fw-semibold">{{ $value }}%</span></div>
  @endisset
  <div class="prog {{ $tone ?? '' }}" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $label ?? 'Progress' }}">
    <span style="width:{{ $value }}%"></span>
  </div>
</div>
