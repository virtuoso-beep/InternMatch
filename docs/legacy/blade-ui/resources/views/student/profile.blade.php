@extends('layouts.app', ['role' => 'student', 'active' => 'profile'])
@section('title','My profile')
@php($pageTitle = 'My profile')
@php($breadcrumbs = ['My profile'])

@section('content')
@include('components.page-header', [
  'title' => 'My profile',
  'subtitle' => 'Keep your information current for accurate matching.',
  'actions' => "<button class='btn btn-brand btn-sm'>Save changes</button>",
])

<form data-autosave>
<div class="row g-3">
  <div class="col-xl-8">
    <div class="mb-3">@include('components.progress', ['value'=>86,'tone'=>'green','label'=>'Profile completeness — add your certifications to reach 100%'])</div>
    <section class="card-x mb-3">
      <div class="card-x-head"><h3>Basic information</h3><span class="autosave ms-auto"><i class="pulse"></i><span>Draft saved just now</span></span></div>
      <div class="card-x-body">
        <div class="d-flex align-items-center gap-3 mb-4">
          <span class="avatar lg">TT</span>
          <div><button class="btn btn-ghost btn-sm" type="button">Change photo</button>
          <p class="form-hint mb-0">JPG or PNG, max 2 MB.</p></div>
        </div>
        <div class="row g-3">
          @foreach([['Full name','Trisha Mae Talamillo','text'],['Student number','2022-00145','text'],['Institutional email','trisha.talamillo@umindanao.edu.ph','email'],['Mobile number','+63 917 555 0142','tel']] as $f)
            <div class="col-md-6"><label class="form-label">{{ $f[0] }}</label><input class="form-control" type="{{ $f[2] }}" value="{{ $f[1] }}"></div>
          @endforeach
          <div class="col-md-6"><label class="form-label" for="prog">Program</label>
            <select class="form-select" id="prog"><option>BS Information Technology</option><option>BS Computer Science</option><option>BS Business Administration</option></select></div>
          <div class="col-md-6"><label class="form-label" for="yr">Year level</label>
            <select class="form-select" id="yr"><option>4th Year</option><option>3rd Year</option></select></div>
          <div class="col-12"><label class="form-label" for="addr">Residence address</label>
            <input class="form-control" id="addr" value="Purok 5, Visayan Village, Tagum City">
            <p class="form-hint">Used only to compute your accessibility score for nearby establishments.</p></div>
        </div>
      </div>
      <div class="card-x-foot d-flex gap-2 justify-content-end"><button class="btn btn-ghost btn-sm" type="button">Discard</button><button class="btn btn-brand btn-sm" type="button" onclick="IMToast('Profile changes saved.','success')">Save changes</button></div>
    </section>
    <section class="card-x">
      <div class="card-x-head"><h3>Internship preferences</h3></div>
      <div class="card-x-body row g-3">
        <div class="col-md-6"><label class="form-label" for="pt">Preferred track</label><select class="form-select" id="pt"><option>Software Development</option><option>Systems Support</option><option>Data Analytics</option></select></div>
        <div class="col-md-6"><label class="form-label" for="pd">Maximum travel distance</label><select class="form-select" id="pd"><option>Within 5 km</option><option>Within 10 km</option><option>No limit</option></select></div>
        <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="sw1" checked><label class="form-check-label fs-13" for="sw1">Allow my profile to be shown to accredited host establishments</label></div></div>
      </div>
    </section>
  </div>
  <div class="col-xl-4">
    <section class="card-x mb-3"><div class="card-x-head"><h3>Verification status</h3></div><div class="card-x-body d-grid gap-2">
      @foreach([['Enrollment verified','success','check-circle'],['Email verified','success','check-circle'],['Medical clearance','warning','clock'],['Parental consent','success','check-circle']] as $v)
        <div class="d-flex justify-content-between align-items-center"><span class="fs-13">{{ $v[0] }}</span>@include('components.badge',['type'=>$v[1],'label'=>ucfirst($v[1]) === 'Success' ? 'Complete' : 'Pending','icon'=>$v[2]])</div>
      @endforeach
    </div></section>
    <section class="card-x"><div class="card-x-head"><h3>Adviser</h3></div><div class="card-x-body d-flex gap-2 align-items-center">
      <span class="avatar">MC</span><div><strong class="d-block text-heading fs-13">Ms. Marissa Cortez</strong><span class="fs-12 text-muted-2">Practicum Coordinator</span></div>
    </div></section>
  </div>
</div>
</form>
@endsection
