{{-- @include('components.timeline', ['items'=>[['title'=>'','text'=>'','state'=>'done'],...]]) --}}
<div class="timeline">
  @foreach($items as $item)
    <div class="tl-item {{ $item['state'] ?? '' }}">
      <h6>{{ $item['title'] }}</h6>
      <p>{{ $item['text'] }}</p>
    </div>
  @endforeach
</div>
