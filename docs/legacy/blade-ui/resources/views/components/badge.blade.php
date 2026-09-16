{{-- @include('components.badge', ['type'=>'success','label'=>'Approved','icon'=>'check-circle']) --}}
@php $type = $type ?? 'neutral'; @endphp
<span class="badge-x badge-{{ $type }}">@isset($icon)<i class="bi bi-{{ $icon }}"></i>@endisset{{ $label }}</span>
