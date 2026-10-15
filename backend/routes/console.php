<?php

use App\Models\Placement;
use App\Models\StudentEnrollment;
use App\Services\ChatAccess;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('internmatch:refresh-monitoring')->hourly()->withoutOverlapping();

Artisan::command('internmatch:sync-chat', function () {
    StudentEnrollment::each(fn ($e) => ChatAccess::ensure($e));
    Placement::each(fn ($p) => ChatAccess::ensure($p->studentEnrollment, $p));
    $this->info('Enrollment and approved deployment conversations synchronized. No messages sent.');
});

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
