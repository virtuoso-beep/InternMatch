@extends('layouts.app', ['role' => $role ?? 'student', 'active' => ''])
@section('title','Account profile')
@php($pageTitle = 'Account profile')
@php($breadcrumbs = ['Settings','Account profile'])
@section('content')
@include('components.page-header', ['title'=>'Account profile','subtitle'=>'Your personal details across InternMatch.'])
<div class="nav-x mb-4">
  <a href="{{ route('settings.profile') }}" class="active">Profile</a>
  <a href="{{ route('settings.preferences') }}" class="">Preferences</a>
  <a href="{{ route('settings.security') }}" class="">Security</a>
</div>

<div class="row g-3"><div class="col-xl-8"><section class="card-x"><div class="card-x-body">
  <div class="d-flex align-items-center gap-3 mb-4"><span class="avatar lg">TT</span>
  <div><button class="btn btn-ghost btn-sm">Change photo</button><p class="form-hint mb-0">JPG or PNG, max 2 MB.</p></div></div>
  <form class="row g-3" data-demo>
    <div class="col-md-6"><label class="form-label" for="s1">Display name</label><input class="form-control" id="s1" value="Trisha Talamillo"></div>
    <div class="col-md-6"><label class="form-label" for="s2">Email</label><input class="form-control" id="s2" value="trisha.talamillo@umindanao.edu.ph"></div>
    <div class="col-md-6"><label class="form-label" for="s3">Mobile</label><input class="form-control" id="s3" value="+63 917 555 0142"></div>
    <div class="col-md-6"><label class="form-label" for="s4">Language</label><select class="form-select" id="s4"><option>English</option><option>Filipino</option></select></div>
    <div class="col-12"><button class="btn btn-brand btn-sm">Save changes</button></div>
  </form>
</div></section></div></div>
@endsection
