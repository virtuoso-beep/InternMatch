<?php

namespace App\Console\Commands;

use App\Enums\MoaStatus;
use App\Enums\Role;
use App\Models\Moa;
use App\Models\User;
use App\Notifications\PortalNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Spec §5: when an active MOA has 90 days or fewer left, notify the coordinators of the
 * programs it covers and every administrator — once per agreement per recipient.
 */
class NotifyExpiringMoas extends Command
{
    protected $signature = 'internmatch:notify-expiring-moas';

    protected $description = 'Notify coordinators and administrators about agreements entering the 90-day expiry window';

    public function handle(): int
    {
        $sent = 0;
        Moa::query()->where('status', MoaStatus::Active)
            ->whereDate('expires_on', '>=', today())->whereDate('expires_on', '<=', today()->addDays(Moa::EXPIRING_DAYS))
            ->with(['hostEstablishment:id,name', 'programs:id,code'])->each(function (Moa $moa) use (&$sent) {
                if ($moa->statusOn() !== 'expiring') {
                    return;
                }
                $programIds = $moa->programs->pluck('id');
                $recipients = User::query()->where('role', Role::Admin)->get()->concat(
                    User::query()->where('role', Role::Coordinator)
                        ->when(! $moa->is_institution_wide, fn ($q) => $q->whereHas('programs', fn ($p) => $p->whereIn('programs.id', $programIds)))
                        ->get());
                foreach ($recipients->unique('id') as $user) {
                    $already = DB::table('notifications')->where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->id)
                        ->where('data->moa_expiring_id', $moa->id)->exists();
                    if ($already) {
                        continue;
                    }
                    $user->notify(new PortalNotification('Agreement expiring soon',
                        sprintf('MOA %s with %s (%s) expires on %s.', $moa->reference_number, $moa->hostEstablishment?->name ?? 'a host establishment',
                            $moa->is_institution_wide ? 'all programs' : $moa->programs->pluck('code')->join(', '), $moa->expires_on->toFormattedDateString()),
                        ['moa_expiring_id' => $moa->id]));
                    $sent++;
                }
            });
        $this->info("Expiring-agreement notifications sent: {$sent}.");

        return self::SUCCESS;
    }
}
