<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\Role;
use App\Services\RecommendationLearning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * "Recommendation learning" (spec §9).
 * - Administrators: live label counts, how recommendations are ranked, run history, technical details, and
 *   the "Update recommendations" action.
 * - Coordinators: how recommendations are ranked and the live label counts for their own programs, read only.
 */
class RecommendationLearningController extends Controller
{
    public function show(Request $request, RecommendationLearning $learning): JsonResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, [Role::Admin, Role::Coordinator], true), 403);
        $status = $learning->status();
        $ranking = ['mode' => $status['active'] ? 'learned' : 'similarity', 'test_mode' => $status['active'] && $status['test_model']];

        if ($user->role === Role::Coordinator) {
            $programIds = $user->programs()->pluck('programs.id')->all();

            return response()->json(['role' => 'coordinator', 'ranking' => $ranking,
                'counts' => $learning->counts($programIds)], headers: ['Cache-Control' => 'no-store']);
        }
        abort_unless($user->hasPermission(Permission::ManageSystem), 403);
        $runs = DB::table('ranker_runs')->orderByDesc('id')->limit(20)->get();
        $card = $status['card'];

        return response()->json([
            'role' => 'admin',
            'ranking' => $ranking,
            'counts' => $learning->counts(),
            'test_mode_available' => RecommendationLearning::testModeAllowed(),
            'last_run' => $runs->first() ? $this->run($runs->first(), true) : null,
            'history' => $runs->map(fn ($run) => $this->run($run))->values(),
            'technical' => [
                'status' => $status['active'] ? 'Logistic Regression model active' : 'Similarity fallback (no model in use)',
                'reason' => $status['reason'],
                'model' => $card,
                'fallback' => $learning->fallbackEvaluation($learning->realRows()),
            ],
        ], headers: ['Cache-Control' => 'no-store']);
    }

    public function update(Request $request, RecommendationLearning $learning): JsonResponse
    {
        abort_unless($request->user()->role === Role::Admin && $request->user()->hasPermission(Permission::ManageSystem), 403);
        $data = $request->validate(['test_data' => ['sometimes', 'boolean']]);
        $run = $learning->run($request->user(), 'admin', (bool) ($data['test_data'] ?? false));

        return response()->json(['data' => $this->run($run, true)]);
    }

    private function run(object $run, bool $details = false): array
    {
        $row = ['id' => $run->id, 'started_at' => $run->started_at, 'result' => $run->result, 'used_test_data' => (bool) $run->used_test_data,
            'suitable' => (int) $run->suitable, 'not_suitable' => (int) $run->not_suitable];

        return $details ? $row + ['reason' => $run->reason, 'uncertain' => (int) $run->uncertain, 'students' => (int) $run->students,
            'metrics' => $run->metrics ? json_decode($run->metrics, true) : null, 'fallback' => $run->fallback ? json_decode($run->fallback, true) : null,
            'model_trained_at' => $run->model_trained_at] : $row;
    }
}
