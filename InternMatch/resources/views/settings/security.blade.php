@extends('layouts.app', ['role' => $role ?? 'student', 'active' => ''])
@section('title','Security')
@php($pageTitle = 'Security')
@php($breadcrumbs = ['Settings','Security'])
@section('content')
@include('components.page-header', ['title'=>'Security','subtitle'=>'Password and session management.'])
<div class="nav-x mb-4">
  <a href="{{ route('settings.profile') }}" class="">Profile</a>
  <a href="{{ route('settings.preferences') }}" class="">Preferences</a>
  <a href="{{ route('settings.security') }}" class="active">Security</a>
</div>

<div class="row g-3"><div class="col-xl-6"><section class="card-x"><div class="card-x-head"><h3>Change password</h3></div><div class="card-x-body">
  <form class="d-grid gap-3" data-demo>
    <div><label class="form-label" for="cp">Current password</label><input type="password" class="form-control" id="cp"></div>
    <div><label class="form-label" for="npw">New password</label><input type="password" class="form-control" id="npw"></div>
    <div><label class="form-label" for="cpw">Confirm new password</label><input type="password" class="form-control" id="cpw"></div>
    <button class="btn btn-brand btn-sm">Update password</button>
  </form>
</div></section></div>
<div class="col-xl-6"><section class="card-x"><div class="card-x-head"><h3>Active sessions</h3></div><div class="card-x-body d-grid gap-2">
  @foreach([['Chrome on Windows','Tagum City · current session',true],['Safari on iPhone','Tagum City · 2 days ago',false]] as $s)
    <div class="d-flex align-items-center gap-2 p-2" style="border:1px solid var(--border);border-radius:10px">
      <i class="bi bi-laptop text-muted-2"></i><div class="flex-grow-1"><span class="d-block fs-13 text-heading">{{ $s[0] }}</span><span class="fs-11 text-muted-2">{{ $s[1] }}</span></div>
      @if($s[2])<span class="badge-x badge-success">Current</span>@else<button class="btn btn-ghost btn-sm">Revoke</button>@endif
    </div>
  @endforeach
</div></section></div></div>
@endsection
