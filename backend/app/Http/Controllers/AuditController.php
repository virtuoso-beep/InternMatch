<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission(Permission::ViewAudit), 403);
        $query = DB::table('audit_logs')->leftJoin('users', 'users.id', '=', 'audit_logs.actor_id')
            ->select('audit_logs.id', 'audit_logs.action', 'audit_logs.program_id', 'audit_logs.subject_type', 'audit_logs.subject_id', 'audit_logs.created_at', 'users.name as actor_name');
        if ($user->role !== Role::Admin) {
            $query->whereIn('audit_logs.program_id', $user->programs()->select('programs.id'));
        }

        return response()->json($query->orderByDesc('audit_logs.id')->paginate(50), headers: ['Cache-Control' => 'no-store']);
    }
}
