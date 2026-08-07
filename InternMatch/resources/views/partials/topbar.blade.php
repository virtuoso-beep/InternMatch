@php
  $role = $role ?? 'student';
  $pageTitle = $pageTitle ?? ($title ?? 'Dashboard');
  $breadcrumbs = $breadcrumbs ?? [];
  $initialsMap = ['admin'=>'RA','student'=>'TT','coordinator'=>'MC','supervisor'=>'RF','chair'=>'JD','dean'=>'AV'];
  $nameMap = ['admin'=>'Engr. Rolando Abella','student'=>'Trisha Talamillo','coordinator'=>'Ms. Marissa Cortez','supervisor'=>'Mr. Rico Fernandez','chair'=>'Dr. Joana Delos Reyes','dean'=>'Dr. Alfonso Villanueva'];
  $roleLabel = ['admin'=>'System Administrator','student'=>'Student Intern','coordinator'=>'Practicum Coordinator','supervisor'=>'Host Supervisor','chair'=>'Program Chair','dean'=>'Dean'];
@endphp
<header class="topbar">
  <button class="icon-btn" onclick="IMToggleSidebar()" aria-label="Toggle navigation"><i class="bi bi-list fs-5"></i></button>

  <div class="d-none d-md-block">
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="#">{{ $roleLabel[$role] ?? 'Workspace' }}</a>
      @foreach($breadcrumbs as $crumb)
        <span class="mx-1">/</span><span>{{ $crumb }}</span>
      @endforeach
    </nav>
    <h1 class="page-title">{{ $pageTitle }}</h1>
  </div>

  <div class="ms-auto d-none d-lg-block search-x">
    <i class="bi bi-search" aria-hidden="true"></i>
    <label for="globalSearch" class="visually-hidden">Search InternMatch</label>
    <input id="globalSearch" type="search" placeholder="Search students, companies, reports…">
    <kbd>⌘K</kbd>
  </div>

  <div class="d-flex align-items-center gap-1 ms-auto ms-lg-0">
    <div class="dropdown">
      <button class="icon-btn" data-bs-toggle="dropdown" aria-label="Quick actions" aria-expanded="false"><i class="bi bi-plus-lg"></i></button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li class="dropdown-header">Quick actions</li>
        <li><a class="dropdown-item" href="#"><i class="bi bi-file-earmark-arrow-up me-2"></i>Upload document</a></li>
        <li><a class="dropdown-item" href="#"><i class="bi bi-journal-plus me-2"></i>New weekly report</a></li>
        <li><a class="dropdown-item" href="#"><i class="bi bi-building-add me-2"></i>Add establishment</a></li>
        <li><a class="dropdown-item" href="#"><i class="bi bi-download me-2"></i>Export data</a></li>
      </ul>
    </div>

    <button class="icon-btn" onclick="IMToggleTheme()" aria-label="Toggle dark mode"><i class="bi bi-moon-stars" data-theme-icon></i></button>

    <div class="dropdown">
      <button class="icon-btn" data-bs-toggle="dropdown" aria-label="Notifications" aria-expanded="false">
        <i class="bi bi-bell"></i><span class="dot"></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end" style="width:320px">
        <li class="dropdown-header d-flex justify-content-between">Notifications <a href="#" class="fs-11">Mark all read</a></li>
        @foreach([
          ['magic','A new 94% match was found for you','2 minutes ago','badge-ai'],
          ['check-circle','Weekly Report #08 approved','1 hour ago','badge-success'],
          ['exclamation-triangle','Medical Certificate is still pending','Yesterday','badge-warning'],
          ['calendar-event','Midterm evaluation opens Monday','2 days ago','badge-info'],
        ] as $n)
        <li><a class="dropdown-item d-flex gap-2 align-items-start" href="#">
          <span class="badge-x {{ $n[3] }}"><i class="bi bi-{{ $n[0] }}"></i></span>
          <span><span class="d-block text-heading" style="font-size:.82rem">{{ $n[1] }}</span>
          <span class="fs-11 text-muted-2">{{ $n[2] }}</span></span>
        </a></li>
        @endforeach
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-center" href="#">View all notifications</a></li>
      </ul>
    </div>

    <div class="dropdown ms-1">
      <button class="btn btn-link p-0 border-0 d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
        <span class="avatar">{{ $initialsMap[$role] ?? 'UM' }}</span>
        <span class="d-none d-xl-block text-start">
          <span class="d-block text-heading" style="font-size:.8rem;font-weight:600">{{ $nameMap[$role] ?? 'User' }}</span>
          <span class="fs-11 text-muted-2">{{ $roleLabel[$role] ?? '' }}</span>
        </span>
        <i class="bi bi-chevron-down fs-12 text-muted-2 d-none d-xl-block"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end" style="width:230px">
        <li class="dropdown-header">Signed in as {{ $nameMap[$role] ?? 'User' }}</li>
        <li><a class="dropdown-item" href="{{ route('settings.profile') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
        <li><a class="dropdown-item" href="{{ route('settings.preferences') }}"><i class="bi bi-sliders me-2"></i>Preferences</a></li>
        <li><a class="dropdown-item" href="{{ route('settings.security') }}"><i class="bi bi-shield-lock me-2"></i>Security</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item danger" href="{{ route('login') }}"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
      </ul>
    </div>
  </div>
</header>
