@php
  $role = $role ?? 'student';
  $active = $active ?? 'dashboard';
  $navigation = [
    'admin' => [
        'MAIN' => [['Dashboard', 'admin.dashboard', 'speedometer2', 'dashboard'], ['User Management', 'admin.users', 'people', 'users'], ['Role Management', 'admin.roles', 'shield-lock', 'roles'], ['Departments', 'admin.departments', 'diagram-3', 'departments']],
        'ACADEMIC' => [['Academic Year', 'admin.academic-year', 'calendar3', 'academic-year'], ['System Settings', 'admin.settings', 'gear', 'settings'], ['Notifications', 'admin.notifications', 'bell', 'notifications']],
        'GOVERNANCE' => [['Reports', 'admin.reports', 'file-earmark-bar-graph', 'reports'], ['Audit Trail', 'admin.audit-trail', 'clock-history', 'audit-trail'], ['Backup & Recovery', 'admin.backup', 'hdd-stack', 'backup']],
    ],
    'student' => [
        'MAIN' => [['Dashboard', 'student.dashboard', 'speedometer2', 'dashboard'], ['My Profile', 'student.profile', 'person-badge', 'profile'], ['Competencies', 'student.competencies', 'stars', 'competencies'], ['Resume', 'student.resume', 'file-earmark-person', 'resume'], ['Portfolio', 'student.portfolio', 'collection', 'portfolio'], ['Certifications', 'student.certifications', 'patch-check', 'certifications']],
        'MATCHING' => [['Recommendations', 'student.recommendations', 'magic', 'recommendations'], ['Why this match', 'student.recommendation-explanation', 'lightbulb', 'recommendation-explanation'], ['Accessibility Map', 'student.accessibility-map', 'geo-alt', 'accessibility-map']],
        'INTERNSHIP' => [['My Internship', 'student.my-internship', 'briefcase', 'my-internship'], ['Requirements', 'student.requirements', 'file-earmark-check', 'requirements'], ['Weekly Reports', 'student.weekly-reports', 'journal-text', 'weekly-reports'], ['Daily Logs', 'student.daily-logs', 'list-check', 'daily-logs'], ['Final Report', 'student.final-report', 'file-earmark-text', 'final-report'], ['Progress', 'student.progress', 'graph-up', 'progress'], ['Notifications', 'student.notifications', 'bell', 'notifications']],
    ],
    'coordinator' => [
        'MAIN' => [['Dashboard', 'coordinator.dashboard', 'speedometer2', 'dashboard'], ['Student Management', 'coordinator.students', 'mortarboard', 'students'], ['Companies', 'coordinator.companies', 'buildings', 'companies'], ['Internship Opportunities', 'coordinator.opportunities', 'briefcase', 'opportunities'], ['Internship Slots', 'coordinator.slots', 'grid-3x3-gap', 'slots'], ['MOA Management', 'coordinator.moa', 'file-earmark-ruled', 'moa']],
        'INTELLIGENCE' => [['AI Recommendations', 'coordinator.recommendations', 'magic', 'recommendations'], ['Recommendation Explanation', 'coordinator.recommendation-explanation', 'lightbulb', 'recommendation-explanation'], ['Accessibility Analysis', 'coordinator.accessibility-analysis', 'geo-alt', 'accessibility-analysis']],
        'OPERATIONS' => [['Pending Approvals', 'coordinator.approvals', 'inbox', 'approvals'], ['Internship Monitoring', 'coordinator.monitoring', 'activity', 'monitoring'], ['Reports', 'coordinator.reports', 'file-earmark-bar-graph', 'reports'], ['Analytics', 'coordinator.analytics', 'bar-chart', 'analytics'], ['Notifications', 'coordinator.notifications', 'bell', 'notifications']],
    ],
    'supervisor' => [
        'MAIN' => [['Dashboard', 'supervisor.dashboard', 'speedometer2', 'dashboard'], ['Assigned Interns', 'supervisor.interns', 'people', 'interns'], ['Internship Opportunities', 'supervisor.opportunities', 'briefcase', 'opportunities']],
        'MONITORING' => [['Attendance', 'supervisor.attendance', 'calendar-check', 'attendance'], ['Student Evaluation', 'supervisor.evaluation', 'clipboard-check', 'evaluation'], ['Progress Monitoring', 'supervisor.progress', 'activity', 'progress'], ['Notifications', 'supervisor.notifications', 'bell', 'notifications']],
    ],
    'chair' => [
        'MAIN' => [['Dashboard', 'chair.dashboard', 'speedometer2', 'dashboard'], ['Analytics', 'chair.analytics', 'bar-chart', 'analytics'], ['Reports', 'chair.reports', 'file-earmark-bar-graph', 'reports']],
        'PROGRAM INSIGHT' => [['Company Performance', 'chair.company-performance', 'building-check', 'company-performance'], ['Competency Coverage', 'chair.competency-coverage', 'stars', 'competency-coverage'], ['Deployment Overview', 'chair.deployment', 'diagram-3', 'deployment'], ['Coordinator Performance', 'chair.coordinator-performance', 'person-check', 'coordinator-performance']],
    ],
    'dean' => [
        'EXECUTIVE' => [['Executive Dashboard', 'dean.dashboard', 'speedometer2', 'dashboard'], ['Institutional Analytics', 'dean.analytics', 'bar-chart', 'analytics'], ['College Statistics', 'dean.statistics', 'pie-chart', 'statistics']],
        'INSIGHT' => [['Reports', 'dean.reports', 'file-earmark-bar-graph', 'reports'], ['Placement Distribution', 'dean.placement-distribution', 'geo', 'placement-distribution'], ['Completion Rates', 'dean.completion-rates', 'graph-up-arrow', 'completion-rates'], ['Accessibility Analytics', 'dean.accessibility-analytics', 'universal-access', 'accessibility-analytics']],
    ],
  ];
  $roleMeta = [
    'admin' => ['name' => 'System Administrator', 'short' => 'Administrator', 'user' => 'Engr. Rolando Abella', 'initials' => 'RA'],
    'student' => ['name' => 'Student Intern', 'short' => 'Student', 'user' => 'Trisha Talamillo', 'initials' => 'TT'],
    'coordinator' => ['name' => 'Practicum Coordinator', 'short' => 'Coordinator', 'user' => 'Ms. Marissa Cortez', 'initials' => 'MC'],
    'supervisor' => ['name' => 'Host Establishment Supervisor', 'short' => 'Supervisor', 'user' => 'Mr. Rico Fernandez', 'initials' => 'RF'],
    'chair' => ['name' => 'Program Chair', 'short' => 'Program Chair', 'user' => 'Dr. Joana Delos Reyes', 'initials' => 'JD'],
    'dean' => ['name' => 'Dean', 'short' => 'Dean', 'user' => 'Dr. Alfonso Villanueva', 'initials' => 'AV'],
  ];
  $menu = $navigation[$role] ?? $navigation['student'];
  $meta = $roleMeta[$role] ?? $roleMeta['student'];
@endphp

<aside class="sidebar" aria-label="Primary navigation">
  <div class="sb-brand">
    <span class="sb-mark" aria-hidden="true">IM</span>
    <span class="sb-brand-text">
      <strong>InternMatch</strong>
      <span>UM Tagum College</span>
    </span>
  </div>

  <nav class="sb-nav">
    @foreach($menu as $group => $links)
      <div class="sb-group">{{ $group }}</div>
      @foreach($links as $link)
        @php([$label, $routeName, $icon, $slug] = $link)
        <a class="sb-link {{ $active === $slug ? 'active' : '' }}"
           href="{{ Route::has($routeName) ? route($routeName) : '#' }}"
           @if($active === $slug) aria-current="page" @endif
           title="{{ $label }}">
          <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
          <span>{{ $label }}</span>
          @if($slug === 'notifications')<span class="sb-count">4</span>@endif
        </a>
      @endforeach
    @endforeach
  </nav>

  <div class="sb-foot d-flex align-items-center gap-2">
    <span class="avatar sm gold">{{ $meta['initials'] }}</span>
    <span class="flex-grow-1">
      <span class="d-block text-white" style="font-size:.78rem">{{ $meta['user'] }}</span>
      <span>{{ $meta['short'] }}</span>
    </span>
    <a href="{{ route('login') }}" class="text-white-50" title="Sign out" aria-label="Sign out"><i class="bi bi-box-arrow-right"></i></a>
  </div>
</aside>
