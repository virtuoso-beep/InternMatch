# InternMatch — Front-End Package
**An Intelligent Student Internship Placement and Monitoring System**
University of Mindanao Tagum College

Laravel Blade + HTML5 + CSS3 + Bootstrap 5 + vanilla JavaScript. No React, Vue, Tailwind or jQuery.

## Install into a Laravel app

1. Copy `resources/views/*` into your app's `resources/views/`.
2. Copy `resources/css/internmatch.css` to `public/css/internmatch.css`.
3. Copy `resources/js/internmatch.js` to `public/js/internmatch.js`.
   (Or keep them under `resources/` and compile with Vite — then swap the
   `asset()` calls in the layouts for `@vite([...])`.)
4. Merge `routes/web.php` into your route file.
5. Visit `/` for the public site and `/login` for authentication.

## Structure
```
resources/views/
  layouts/     public.blade.php · auth.blade.php · app.blade.php
  partials/    sidebar · topbar · public-nav · public-footer · faq-list
  components/  page-header, card, stat-card, badge, alert, progress, table-toolbar,
               pagination, avatar, timeline, confidence-meter, rec-card, empty-state
  public/      landing, about, features, how-it-works, faq, contact,
               privacy, terms, accessibility, help
  auth/        login, forgot-password, reset-password
  admin/ student/ coordinator/ supervisor/ chair/ dean/   role modules
  settings/    profile, preferences, security
```

## Using the app shell
```blade
@extends('layouts.app', ['role' => 'student', 'active' => 'dashboard'])
@section('title','Dashboard')
@php($pageTitle = 'Dashboard')
@section('content') ... @endsection
```
`role` selects the sidebar navigation; `active` highlights the current item.

## Design tokens
All colours, radii, and shadows are CSS custom properties at the top of
`internmatch.css` (UM Crimson `#A2272C`, Deep Maroon `#7D1F26`, UM Gold `#E6B21E`,
Soft Gold `#F4D35E`, Lavender `#B8B4D9`, plus neutrals and status colours).
Dark mode re-maps the same tokens under `body.dark`.

## JavaScript API
`IMToast(message, type)` · `IMToggleTheme()` · `IMToggleSidebar()`.
Data attributes: `data-count`, `data-table-search`, `data-sort`, `data-check-all`,
`data-chip-group`, `data-tabs` / `data-tab-target` / `data-tab-panel`,
`data-autosave`, `.reveal`, `.dropzone`.

## Notes
- All data shown is illustrative placeholder content; wire it to controllers.
- Accessibility: semantic landmarks, skip links, visible focus rings, ARIA labels,
  and `prefers-reduced-motion` support.
