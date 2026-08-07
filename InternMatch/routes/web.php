<?php

use Illuminate\Support\Facades\Route;

/*
| InternMatch — front-end route map.
| These are view-only routes so the whole UI is browsable without a backend.
| Replace Route::view(...) with controller actions as you build the backend.
*/

Route::view('/', 'public.landing')->name('landing');

Route::prefix('/')->name('public.')->group(function () {
    Route::view('about', 'public.about')->name('about');
    Route::view('features', 'public.features')->name('features');
    Route::view('how-it-works', 'public.how-it-works')->name('how-it-works');
    Route::view('faq', 'public.faq')->name('faq');
    Route::view('contact', 'public.contact')->name('contact');
    Route::view('privacy', 'public.privacy')->name('privacy');
    Route::view('terms', 'public.terms')->name('terms');
    Route::view('accessibility', 'public.accessibility')->name('accessibility');
    Route::view('help', 'public.help')->name('help');
});

// Authentication
Route::view('login', 'auth.login')->name('login');
Route::post('login', function () { return redirect()->route('student.dashboard'); })->name('login.attempt');
Route::view('forgot-password', 'auth.forgot-password')->name('password.request');
Route::post('forgot-password', function () { return back(); })->name('password.email');
Route::view('reset-password', 'auth.reset-password')->name('password.reset');
Route::post('reset-password', function () { return redirect()->route('login'); })->name('password.update');

// Shared settings
Route::prefix('settings')->name('settings.')->group(function () {
    Route::view('profile', 'settings.profile')->name('profile');
    Route::view('preferences', 'settings.preferences')->name('preferences');
    Route::view('security', 'settings.security')->name('security');
});

// Admin workspace
Route::prefix('admin')->name('admin.')->group(function () {
    Route::view('academic-year', 'admin.academic-year')->name('academic-year');
    Route::view('audit-trail', 'admin.audit-trail')->name('audit-trail');
    Route::view('backup', 'admin.backup')->name('backup');
    Route::view('dashboard', 'admin.dashboard')->name('dashboard');
    Route::view('departments', 'admin.departments')->name('departments');
    Route::view('notifications', 'admin.notifications')->name('notifications');
    Route::view('reports', 'admin.reports')->name('reports');
    Route::view('roles', 'admin.roles')->name('roles');
    Route::view('settings', 'admin.settings')->name('settings');
    Route::view('users', 'admin.users')->name('users');
});

// Student workspace
Route::prefix('student')->name('student.')->group(function () {
    Route::view('accessibility-map', 'student.accessibility-map')->name('accessibility-map');
    Route::view('certifications', 'student.certifications')->name('certifications');
    Route::view('competencies', 'student.competencies')->name('competencies');
    Route::view('daily-logs', 'student.daily-logs')->name('daily-logs');
    Route::view('dashboard', 'student.dashboard')->name('dashboard');
    Route::view('final-report', 'student.final-report')->name('final-report');
    Route::view('my-internship', 'student.my-internship')->name('my-internship');
    Route::view('notifications', 'student.notifications')->name('notifications');
    Route::view('portfolio', 'student.portfolio')->name('portfolio');
    Route::view('profile', 'student.profile')->name('profile');
    Route::view('progress', 'student.progress')->name('progress');
    Route::view('recommendation-explanation', 'student.recommendation-explanation')->name('recommendation-explanation');
    Route::view('recommendations', 'student.recommendations')->name('recommendations');
    Route::view('requirements', 'student.requirements')->name('requirements');
    Route::view('resume', 'student.resume')->name('resume');
    Route::view('weekly-reports', 'student.weekly-reports')->name('weekly-reports');
});

// Coordinator workspace
Route::prefix('coordinator')->name('coordinator.')->group(function () {
    Route::view('accessibility-analysis', 'coordinator.accessibility-analysis')->name('accessibility-analysis');
    Route::view('analytics', 'coordinator.analytics')->name('analytics');
    Route::view('approvals', 'coordinator.approvals')->name('approvals');
    Route::view('companies', 'coordinator.companies')->name('companies');
    Route::view('dashboard', 'coordinator.dashboard')->name('dashboard');
    Route::view('moa', 'coordinator.moa')->name('moa');
    Route::view('monitoring', 'coordinator.monitoring')->name('monitoring');
    Route::view('notifications', 'coordinator.notifications')->name('notifications');
    Route::view('opportunities', 'coordinator.opportunities')->name('opportunities');
    Route::view('recommendation-explanation', 'coordinator.recommendation-explanation')->name('recommendation-explanation');
    Route::view('recommendations', 'coordinator.recommendations')->name('recommendations');
    Route::view('reports', 'coordinator.reports')->name('reports');
    Route::view('slots', 'coordinator.slots')->name('slots');
    Route::view('students', 'coordinator.students')->name('students');
});

// Supervisor workspace
Route::prefix('supervisor')->name('supervisor.')->group(function () {
    Route::view('attendance', 'supervisor.attendance')->name('attendance');
    Route::view('dashboard', 'supervisor.dashboard')->name('dashboard');
    Route::view('evaluation', 'supervisor.evaluation')->name('evaluation');
    Route::view('interns', 'supervisor.interns')->name('interns');
    Route::view('notifications', 'supervisor.notifications')->name('notifications');
    Route::view('opportunities', 'supervisor.opportunities')->name('opportunities');
    Route::view('progress', 'supervisor.progress')->name('progress');
});

// Chair workspace
Route::prefix('chair')->name('chair.')->group(function () {
    Route::view('analytics', 'chair.analytics')->name('analytics');
    Route::view('company-performance', 'chair.company-performance')->name('company-performance');
    Route::view('competency-coverage', 'chair.competency-coverage')->name('competency-coverage');
    Route::view('coordinator-performance', 'chair.coordinator-performance')->name('coordinator-performance');
    Route::view('dashboard', 'chair.dashboard')->name('dashboard');
    Route::view('deployment', 'chair.deployment')->name('deployment');
    Route::view('reports', 'chair.reports')->name('reports');
});

// Dean workspace
Route::prefix('dean')->name('dean.')->group(function () {
    Route::view('accessibility-analytics', 'dean.accessibility-analytics')->name('accessibility-analytics');
    Route::view('analytics', 'dean.analytics')->name('analytics');
    Route::view('completion-rates', 'dean.completion-rates')->name('completion-rates');
    Route::view('dashboard', 'dean.dashboard')->name('dashboard');
    Route::view('placement-distribution', 'dean.placement-distribution')->name('placement-distribution');
    Route::view('reports', 'dean.reports')->name('reports');
    Route::view('statistics', 'dean.statistics')->name('statistics');
});

