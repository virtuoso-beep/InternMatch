{{-- @include('components.alert', ['type'=>'info','message'=>'...','title'=>'']) --}}
@php $type=$type??'info'; $ico=['info'=>'info-circle-fill','success'=>'check-circle-fill','warn'=>'exclamation-triangle-fill','danger'=>'x-circle-fill','ai'=>'stars'][$type] ?? 'info-circle-fill'; @endphp
<div class="alert-x {{ $type }} mb-3" role="alert">
  <i class="bi bi-{{ $ico }} mt-1"></i>
  <div>@isset($title)<strong class="d-block">{{ $title }}</strong>@endisset{!! $message !!}</div>
</div>
