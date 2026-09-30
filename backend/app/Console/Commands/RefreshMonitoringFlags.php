<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\MonitoringFlag;
use App\Models\Placement;
use App\Models\User;
use App\Notifications\PortalNotification;
use App\Services\ProgressSummary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshMonitoringFlags extends Command
{
    protected $signature = 'internmatch:refresh-monitoring';
    protected $description = 'Persist current program monitoring flags and notify only on newly raised conditions';

    public function handle(ProgressSummary $progress): int
    {
        $raised = 0;
        Placement::where(fn ($q) => $q->whereIn('status', ['approved', 'active'])->orWhereHas('monitoringFlags', fn ($flags) => $flags->where('code', 'like', 'auto:%')->whereNull('resolved_at')))->orderBy('id')->chunkById(100, function ($placements) use ($progress, &$raised) {
            foreach ($placements as $source) {
                $raised += DB::transaction(function () use ($source, $progress) {
                    $placement = Placement::whereKey($source->id)->lockForUpdate()->firstOrFail();
                    $program = $placement->studentEnrollment->programTerm->program_id;
                    $current = in_array($placement->status->value, ['approved', 'active'], true) ? $progress->forPlacement($placement)['flags'] : [];
                    $codes = array_map(fn ($flag) => 'auto:'.$flag['code'], $current);
                    $resolved = $placement->monitoringFlags()->where('code', 'like', 'auto:%')->whereNull('resolved_at')->whereNotIn('code', $codes)->get();
                    foreach ($resolved as $flag) {
                        $flag->update(['resolved_at' => now(), 'resolved_automatically' => true, 'resolution' => 'The configured condition no longer applies.']);
                        $this->audit($flag, $program, 'monitoring.resolved');
                    }
                    $recipients = User::where('role', Role::Coordinator)->where('status', 'active')->whereHas('programs', fn ($q) => $q->whereKey($program))->get();
                    $recipients->push($placement->studentEnrollment->student->user);
                    if ($placement->supervisor && $placement->supervisor->isAssignedToHost($placement->host_establishment_id)) { $recipients->push($placement->supervisor); }
                    $count = 0;
                    foreach ($current as $condition) {
                        $code = 'auto:'.$condition['code'];
                        if ($placement->monitoringFlags()->where('code', $code)->whereNull('resolved_at')->exists()) { continue; }
                        $flag = $placement->monitoringFlags()->create(['code' => $code, 'severity' => 'warning', 'description' => $condition['description'], 'raised_at' => now()]);
                        $this->audit($flag, $program, 'monitoring.raised');
                        foreach ($recipients->unique('id') as $user) {
                            if ($user->status->value === 'active') {
                                $user->notify(new PortalNotification('Internship progress needs attention', 'Placement #'.$placement->id.': '.$condition['description']));
                            }
                        }
                        $count++;
                    }

                    return $count;
                }, 3);
            }
        });
        $this->info($raised.' new monitoring condition(s) raised.');

        return self::SUCCESS;
    }

    private function audit(MonitoringFlag $flag, int $program, string $action): void
    {
        DB::table('audit_logs')->insert(['actor_id' => null, 'program_id' => $program, 'action' => $action, 'subject_type' => $flag->getMorphClass(), 'subject_id' => $flag->id, 'changes' => json_encode(['code' => $flag->code, 'source' => 'scheduled_program_rules']), 'created_at' => now()]);
    }
}
