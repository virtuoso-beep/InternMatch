<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Http\Request;

class BackupStatusController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()->role === Role::Admin, 403);
        $records = [];
        foreach (glob(storage_path('app/backups/*/manifest.json')) ?: [] as $file) {
            $item = json_decode(file_get_contents($file), true);
            if (! is_array($item) || ! isset($item['id'], $item['status'])) {
                continue;
            }
            $records[] = [
                'id' => $item['id'], 'status' => $item['status'],
                'created_at' => $item['created_at'] ?? null,
                'verified_at' => $item['verified_at'] ?? null,
                'bytes' => array_sum(array_column($item['files'] ?? [], 'bytes')),
                'verification_scope' => $item['verification']['scope'] ?? null,
            ];
        }
        usort($records, fn ($a, $b) => strcmp($b['id'], $a['id']));
        $scheduleFile = storage_path('app/backups/schedule.json');
        $schedule = is_file($scheduleFile) ? json_decode(ltrim(file_get_contents($scheduleFile), "\xEF\xBB\xBF"), true) : null;
        return response()->json(['data' => array_slice($records, 0, 100), 'total' => count($records), 'schedule' => $schedule], headers: ['Cache-Control' => 'no-store']);
    }
}
