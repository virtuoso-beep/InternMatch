@extends('layouts.public')
@section('title','Contact Us')
@section('meta_description','Reach the Practicum Office of the University of Mindanao Tagum College.')
@section('content')
<header class="pt-5" style="background:var(--warm-gray);border-bottom:1px solid var(--border)">
  <div class="container" style="padding:6.5rem 0 3.5rem">
    <nav class="fs-12 text-muted-2 mb-2" aria-label="Breadcrumb"><a href="{{ route('landing') }}">Home</a> <span class="mx-1">/</span> Contact Us</nav>
    <h1>Contact Us</h1>
    <p class="lead-2 mt-2" style="max-width:44rem">Reach the Practicum Office of the University of Mindanao Tagum College.</p>
  </div>
</header>
<section class="section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-5">
        <ul class="list-unstyled d-grid gap-3 fs-13">
          @foreach([['geo-alt','Campus address','Apokon Road, Tagum City, Davao del Norte 8100'],['telephone','Telephone','(084) 216-0000 local 118'],['envelope','Email','internmatch@umindanao.edu.ph'],['clock','Office hours','Monday to Friday · 8:00 AM – 5:00 PM']] as $c)
          <li class="d-flex gap-3"><span class="feature-ico mb-0" style="width:38px;height:38px;font-size:.95rem"><i class="bi bi-{{ $c[0] }}"></i></span>
          <span><strong class="d-block text-heading">{{ $c[1] }}</strong><span class="text-muted-2">{{ $c[2] }}</span></span></li>
          @endforeach
        </ul>
        <div class="map-ph mt-3"><div class="map-pin" style="top:44%;left:46%"><b>UM</b></div>
        <span class="position-absolute bottom-0 start-0 m-2 fs-11 text-muted-2">Google Maps embed placeholder</span></div>
      </div>
      <div class="col-lg-7">
        <div class="card-x"><div class="card-x-head"><h3>Send a message</h3></div><div class="card-x-body">
          <form class="row g-3" data-demo>
            <div class="col-md-6"><div class="form-floating"><input class="form-control" id="n1" placeholder="Name" required><label for="n1">Full name</label></div></div>
            <div class="col-md-6"><div class="form-floating"><input type="email" class="form-control" id="n2" placeholder="Email" required><label for="n2">Email address</label></div></div>
            <div class="col-12"><div class="form-floating"><input class="form-control" id="n3" placeholder="Subject"><label for="n3">Subject</label></div></div>
            <div class="col-12"><div class="form-floating"><textarea class="form-control" id="n4" style="height:150px" placeholder="Message"></textarea><label for="n4">Message</label></div></div>
            <div class="col-12"><button class="btn btn-brand">Send message</button></div>
          </form>
        </div></div>
      </div>
    </div>
  </div>
</section>
@endsection
