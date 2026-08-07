@extends('layouts.app', ['role' => $role ?? 'student', 'active' => ''])
@section('title','Preferences')
@php($pageTitle = 'Preferences')
@php($breadcrumbs = ['Settings','Preferences'])
@section('content')
@include('components.page-header', ['title'=>'Preferences','subtitle'=>'Interface and notification behaviour.'])
<div class="nav-x mb-4">
  <a href="{{ route('settings.profile') }}" class="">Profile</a>
  <a href="{{ route('settings.preferences') }}" class="active">Preferences</a>
  <a href="{{ route('settings.security') }}" class="">Security</a>
</div>

<div class="row g-3"><div class="col-xl-8"><section class="card-x mb-3"><div class="card-x-head"><h3>Appearance</h3></div><div class="card-x-body">
  <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="dm" onchange="IMToggleTheme()"><label class="form-check-label fs-13" for="dm">Dark mode</label></div>
  <div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" id="cs" checked><label class="form-check-label fs-13" for="cs">Remember collapsed sidebar</label></div>
  <div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" id="rm"><label class="form-check-label fs-13" for="rm">Reduce motion</label></div>
</div></section>
<section class="card-x"><div class="card-x-head"><h3>Notifications</h3></div><div class="card-x-body d-grid gap-2">
  @foreach([['Email notifications',true],['New recommendation alerts',true],['Requirement reminders',true],['Weekly digest',false]] as $n)
    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" @checked($n[1]) id="pn{{ $loop->index }}"><label class="form-check-label fs-13" for="pn{{ $loop->index }}">{{ $n[0] }}</label></div>
  @endforeach
</div></section></div></div>
@endsection
