{{-- @include('components.card', ['title'=>'', 'body'=>'<html>', 'action'=>'', 'foot'=>'']) --}}
<section class="card-x h-100">
  @isset($title)
  <div class="card-x-head">
    <h3>{{ $title }}</h3>
    @isset($subtitle)<span class="fs-12 text-muted-2">{{ $subtitle }}</span>@endisset
    @isset($action)<div class="ms-auto">{!! $action !!}</div>@endisset
  </div>
  @endisset
  <div class="card-x-body">{!! $body ?? ($slot ?? '') !!}</div>
  @isset($foot)<div class="card-x-foot">{!! $foot !!}</div>@endisset
</section>
