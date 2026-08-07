<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
  <span class="fs-12 text-muted-2">Showing <strong>{{ $from ?? 1 }}–{{ $to ?? 10 }}</strong> of <strong>{{ $total ?? 128 }}</strong> records</span>
  <nav class="page-x" aria-label="Pagination">
    <a href="#" aria-label="Previous"><i class="bi bi-chevron-left"></i></a>
    <a href="#" class="active" aria-current="page">1</a>
    <a href="#">2</a><a href="#">3</a><span>…</span><a href="#">13</a>
    <a href="#" aria-label="Next"><i class="bi bi-chevron-right"></i></a>
  </nav>
</div>
