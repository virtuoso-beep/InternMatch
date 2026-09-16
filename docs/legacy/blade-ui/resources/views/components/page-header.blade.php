{{-- @include('components.page-header', ['title'=>'', 'subtitle'=>'', 'actions'=>'<a ...>']) --}}
<div class="page-head d-flex flex-wrap justify-content-between align-items-end gap-3">
  <div>
    <h1>{{ $title }}</h1>
    @isset($subtitle)<p>{{ $subtitle }}</p>@endisset
  </div>
  @isset($actions)<div class="d-flex flex-wrap gap-2 no-print">{!! $actions !!}</div>@endisset
</div>
