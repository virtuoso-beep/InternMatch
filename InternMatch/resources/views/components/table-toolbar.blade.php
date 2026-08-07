{{-- @include('components.table-toolbar', ['target'=>'#tbl','filters'=>['All','Deployed']]) --}}
<div class="table-toolbar">
  <div class="search-x" style="max-width:280px">
    <i class="bi bi-search"></i>
    <label class="visually-hidden" for="tsearch-{{ $id ?? 'a' }}">Search table</label>
    <input id="tsearch-{{ $id ?? 'a' }}" type="search" placeholder="{{ $placeholder ?? 'Search records…' }}" data-table-search="{{ $target ?? '.table-x' }}">
  </div>
  @isset($filters)
    <div class="d-flex gap-2 flex-wrap" data-chip-group>
      @foreach($filters as $i => $f)
        <button class="chip {{ $i === 0 ? 'active' : '' }}" type="button">{{ $f }}</button>
      @endforeach
    </div>
  @endisset
  <div class="ms-auto d-flex gap-2">
    <button class="btn btn-ghost btn-sm" type="button"><i class="bi bi-funnel me-1"></i>Filter</button>
    <div class="dropdown">
      <button class="btn btn-ghost btn-sm" data-bs-toggle="dropdown"><i class="bi bi-download me-1"></i>Export</button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="#">Export as CSV</a></li>
        <li><a class="dropdown-item" href="#">Export as Excel</a></li>
        <li><a class="dropdown-item" href="#">Export as PDF</a></li>
      </ul>
    </div>
  </div>
</div>
