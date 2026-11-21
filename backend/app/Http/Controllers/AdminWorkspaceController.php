<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Filament\Pages\Administration;
use App\Models\AcademicTerm;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit;
use App\Services\ChatAccess;
use App\Support\SyntheticData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminWorkspaceController extends Controller
{
    public function __invoke(Request $request, string $section = 'dashboard')
    {
        abort_unless($section === 'dashboard' || array_key_exists($section, Administration::SECTIONS), 404);
        $path = public_path('admin-ui/.vite/manifest.json');
        $manifest = is_file($path) ? json_decode(file_get_contents($path), true) : [];
        $entry = $manifest['src/admin-entry.tsx'] ?? null;

        return response()->view('admin.workspace', compact('section', 'entry'), $entry ? 200 : 503)->header('Cache-Control', 'no-store')->header('X-Frame-Options', 'SAMEORIGIN');
    }

    private function authorize(Request $request): void
    {
        abort_unless($request->user()->hasPermission(Permission::ManageSystem), 403);
    }

    public function overview(Request $request)
    {
        $this->authorize($request);
        // Synthetic test accounts and their audit rows stay out of the figures unless the switch is on.
        $hideSynthetic = SyntheticData::excluded($request);
        $users = fn () => User::query()->when($hideSynthetic, fn ($query) => $query->where('is_synthetic', false));

        return response()->json([
            'users_by_role' => $users()->selectRaw('role, COUNT(*) AS total')->groupBy('role')->get(),
            'total_users' => $users()->count(),
            'synthetic' => SyntheticData::meta($request),
            'programs' => Program::orderBy('code')->get(['id', 'code', 'name', 'cluster', 'is_active']),
            // Spec: flag any active program that has no active coordinator (nobody can act on its students).
            'programs_without_coordinator' => Program::where('is_active', true)->whereDoesntHave('coordinators', fn ($query) => $query->where('status', 'active'))->orderBy('code')->get(['id', 'code']),
            'terms' => AcademicTerm::orderByDesc('starts_on')->get(),
            'chat_enabled' => ChatAccess::enabled(),
            'recent_activity' => DB::table('audit_logs')->when($hideSynthetic, fn ($query) => $query->where('is_synthetic', false))->orderByDesc('id')->limit(5)->get(['id', 'action', 'created_at']),
        ], headers: ['Cache-Control' => 'no-store']);
    }

    public function term(Request $request, AcademicTerm $term)
    {
        $this->authorize($request);
        $data = $request->validate(['is_active' => 'required|boolean']);
        abort_if($term->archived_at !== null && $data['is_active'], 409, 'Restore the archived term before activating it.');
        DB::transaction(function () use ($request, $term, $data) {
            $before = $term->is_active;
            $term->update($data);
            Audit::record($request->user(), 'academic_term.availability_updated', $term, ['before' => $before, 'after' => $term->is_active]);
        });

        return response()->json(['data' => $term]);
    }

    public function settings(Request $request)
    {
        $this->authorize($request);

        return response()->json(['data' => DB::table('system_preferences')->where('id', 1)->first()], headers: ['Cache-Control' => 'no-store']);
    }

    public function saveSettings(Request $request)
    {
        $this->authorize($request);
        $data = $request->validate(['system_name' => 'required|string|max:80', 'institution' => 'required|string|max:160', 'display_timezone' => 'required|in:Asia/Manila,UTC']);
        DB::transaction(function () use ($request, $data) {
            $before = DB::table('system_preferences')->where('id', 1)->lockForUpdate()->first();
            DB::table('system_preferences')->where('id', 1)->update($data + ['updated_at' => now()]);
            Audit::record($request->user(), 'system.preferences_updated', $request->user(), ['before' => $before, 'after' => $data]);
        });

        return $this->settings($request);
    }

    public function branding(Request $request)
    {
        return response()->json(['data' => DB::table('system_preferences')->where('id', 1)->first(['system_name', 'institution', 'display_timezone'])], headers: ['Cache-Control' => 'no-store']);
    }
}
