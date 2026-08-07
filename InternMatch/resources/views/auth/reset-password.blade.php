@extends('layouts.auth')
@section('title','Reset password')
@section('art_title','Choose a strong new password.')
@section('art_text','Use at least 10 characters with a mix of letters, numbers, and symbols. Never reuse a password from another service.')
@section('content')
<span class="eyebrow">Secure reset</span>
<h1 class="mt-2 mb-1" style="font-size:1.6rem">Set a new password</h1>
<p class="text-muted-2 fs-13 mb-4">You are resetting the password for <strong class="text-heading">{{ $email ?? 'student@umindanao.edu.ph' }}</strong>.</p>
<form method="POST" action="{{ route('password.update') }}" data-demo>
  @csrf
  <input type="hidden" name="token" value="{{ $token ?? '' }}">
  <div class="form-floating mb-3">
    <input type="password" class="form-control" id="np" name="password" placeholder="New password" required minlength="10">
    <label for="np">New password</label>
  </div>
  <div class="prog thin mb-1"><span style="width:66%"></span></div>
  <p class="form-hint mb-3">Password strength: good — add a symbol to reach excellent.</p>
  <div class="form-floating mb-4">
    <input type="password" class="form-control" id="np2" name="password_confirmation" placeholder="Confirm password" required>
    <label for="np2">Confirm new password</label>
  </div>
  <button class="btn btn-brand w-100 btn-lg">Update password</button>
</form>
<p class="fs-13 mt-4 mb-0"><a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a></p>
@endsection
