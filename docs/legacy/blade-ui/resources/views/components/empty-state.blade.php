<div class="text-center py-5">
  <div class="feature-ico mx-auto"><i class="bi bi-{{ $icon ?? 'inbox' }}"></i></div>
  <h3 class="mt-3" style="font-size:1rem">{{ $title ?? 'Nothing here yet' }}</h3>
  <p class="text-muted-2 fs-13 mb-3">{{ $message ?? 'Records will appear once data is available.' }}</p>
  @isset($action){!! $action !!}@endisset
</div>
