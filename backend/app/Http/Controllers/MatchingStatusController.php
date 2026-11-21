<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Which ranking method recommendations currently use, and why. Logistic Regression is active only when the
 * AI service has a validated model built from real coordinator judgments; otherwise cosine similarity is
 * used. Also reports how many real approved judgments exist to train on.
 */
class MatchingStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(in_array($request->user()->role, [Role::Admin, Role::Coordinator, Role::Dean], true), 403);
        $judgments = [
            'total' => DB::table('placement_judgments')->count(),
            'approved' => DB::table('placement_judgments')->whereNotNull('approved_at')->count(),
        ];
        $base = [
            'reranker_setting' => config('matching.reranker'),
            'judgments' => $judgments,
        ];
        if (config('matching.reranker') !== 'auto') {
            return response()->json($base + ['active' => false, 'ranking_method' => 'cosine_then_distance',
                'reason' => 'Supervised reranking is switched off (MATCHING_RERANKER=off).', 'card' => null]);
        }
        try {
            $status = Http::connectTimeout(2)->timeout(4)->get(rtrim(config('matching.url'), '/').'/recommendations/ml/status')->throw()->json();
        } catch (ConnectionException|RequestException) {
            return response()->json($base + ['active' => false, 'ranking_method' => 'cosine_then_distance',
                'reason' => 'The AI service did not report a ranker status; recommendations use cosine similarity.', 'card' => null]);
        }

        return response()->json($base + [
            'active' => (bool) ($status['active'] ?? false),
            'ranking_method' => ($status['active'] ?? false) ? 'logistic_regression' : 'cosine_then_distance',
            'reason' => $status['reason'] ?? null,
            'card' => $status['card'] ?? null,
        ]);
    }
}
