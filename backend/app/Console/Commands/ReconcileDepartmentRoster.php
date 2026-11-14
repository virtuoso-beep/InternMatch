<?php

namespace App\Console\Commands;

use App\Services\DepartmentRoster;
use Illuminate\Console\Command;

class ReconcileDepartmentRoster extends Command
{
    protected $signature = 'internmatch:reconcile-departments {--apply}';
    protected $description = 'Apply the confirmed DAE/DCJE/DCE roster, preserving retired records and existing credentials';

    public function handle(): int
    {
        $this->line(json_encode(['programs' => DepartmentRoster::PROGRAMS, 'coordinators' => DepartmentRoster::COORDINATORS], JSON_PRETTY_PRINT));
        if ($this->option('apply')) {
            $credentials = DepartmentRoster::apply();
            if ($credentials) {
                $path = 'department-roster-passwords-'.now()->format('YmdHis').'.json';
                \Illuminate\Support\Facades\Storage::disk('local')->put($path, json_encode($credentials, JSON_PRETTY_PRINT));
                $this->info('New account credentials saved privately: storage/app/private/'.$path);
            }
            $this->info('Roster applied. Existing passwords preserved.');
        } else {
            $this->info('Preview only. Use --apply after backing up the database.');
        }
        return self::SUCCESS;
    }
}
