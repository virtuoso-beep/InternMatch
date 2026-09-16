@extends('layouts.auth')
@section('title','Forgot password')
@section('art_title','Locked out? We will get you back in.')
@section('art_text','Password recovery is available to all registered InternMatch accounts using your institutional email address.')
@section('content')
<span class="eyebrow">Account recovery</span>
<h1 class="mt-2 mb-1" style="font-size:1.6rem">Forgot your password?</h1>
<p class="text-muted-2 fs-13 mb-4">Enter your institutional email and we will send a secure reset link valid for 60 minutes.</p>
@include('components.alert', ['type'=>'info','message'=>'If your account exists, a reset link will be sent. Check your spam folder if it does not arrive within five minutes.'])
<form method="POST" action="{{ route('password.email') }}" data-demo>
  @csrf
  <div class="form-floating mb-3">
    <input type="email" class="form-control" id="femail" name="email" placeholder="name@umindanao.edu.ph" required autofocus>
    <label for="femail">Institutional email</label>
  </div>
  <button class="btn btn-brand w-100 btn-lg">Send reset link</button>
</form>
<p class="fs-13 mt-4 mb-0"><a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a></p>
@endsection
