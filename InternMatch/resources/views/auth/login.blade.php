@extends('layouts.auth')
@section('title','Sign in')
@section('content')
<div class="d-lg-none mb-4 d-flex align-items-center gap-2">
  <span class="sb-mark">IM</span><strong style="font-family:Poppins">InternMatch</strong>
</div>
<span class="eyebrow">Welcome back</span>
<h1 class="mt-2 mb-1" style="font-size:1.65rem">Sign in to InternMatch</h1>
<p class="text-muted-2 fs-13 mb-4">Use your University of Mindanao account credentials.</p>

<form method="POST" action="{{ route('login.attempt') }}" data-demo>
  @csrf
  <div class="form-floating mb-3">
    <input type="email" class="form-control" id="email" name="email" placeholder="name@umindanao.edu.ph" required autofocus autocomplete="username">
    <label for="email">Institutional email</label>
  </div>
  <div class="form-floating mb-2 position-relative">
    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required autocomplete="current-password">
    <label for="password">Password</label>
    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 text-muted-2 p-1" aria-label="Show password"
      onclick="const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';this.querySelector('i').classList.toggle('bi-eye');this.querySelector('i').classList.toggle('bi-eye-slash');">
      <i class="bi bi-eye"></i>
    </button>
  </div>
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="remember" name="remember">
      <label class="form-check-label fs-13" for="remember">Keep me signed in</label>
    </div>
    <a href="{{ route('password.request') }}" class="fs-13">Forgot password?</a>
  </div>
  <button class="btn btn-brand w-100 btn-lg" type="submit">Sign in <i class="bi bi-arrow-right ms-1"></i></button>
</form>

<div class="d-flex align-items-center gap-3 my-4">
  <span class="divider flex-grow-1"></span><span class="fs-12 text-muted-2">Continue as</span><span class="divider flex-grow-1"></span>
</div>

<div class="row g-2">
  @foreach([['Student','student.dashboard','mortarboard'],['Coordinator','coordinator.dashboard','clipboard-check'],['Supervisor','supervisor.dashboard','buildings'],['Program Chair','chair.dashboard','diagram-3'],['Dean','dean.dashboard','graph-up-arrow'],['Administrator','admin.dashboard','shield-lock']] as $r)
    <div class="col-6"><a href="{{ route($r[1]) }}" class="btn btn-ghost w-100 btn-sm text-start"><i class="bi bi-{{ $r[2] }} me-2 text-crimson"></i>{{ $r[0] }}</a></div>
  @endforeach
</div>
<p class="fs-11 text-muted-2 mt-3 mb-0">Demo shortcuts — replace with real role redirection in your Laravel controller.</p>
<p class="fs-12 text-muted-2 mt-4 mb-0"><a href="{{ route('landing') }}"><i class="bi bi-arrow-left me-1"></i>Back to homepage</a></p>
@endsection
