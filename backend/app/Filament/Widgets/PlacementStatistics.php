<?php

namespace App\Filament\Widgets;

use App\Models\Placement;
use App\Models\TimeLog;
use App\Services\StudentAccess;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlacementStatistics extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        // Synthetic test records are never counted on the management overview.
        $enrollments = StudentAccess::enrollments(Filament::auth()->user())->where('student_enrollments.is_synthetic', false);
        $placements = Placement::whereIn('student_enrollment_id', (clone $enrollments)->select('id'));

        return [
            Stat::make('Enrollment records', (clone $enrollments)->count())->description('Within your program access'),
            Stat::make('Active placements', (clone $placements)->where('status', 'active')->count()),
            Stat::make('Certified hours', round(TimeLog::whereIn('placement_id', (clone $placements)->select('id'))->where('status', 'verified')->sum('credited_minutes') / 60, 2)),
        ];
    }
}
