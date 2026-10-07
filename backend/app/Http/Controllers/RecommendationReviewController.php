<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Services\RecommendationReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationReviewController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === Role::Coordinator, 403);
        $records = RecommendationReview::query($request->user())
            ->with(['studentEnrollment.student.user', 'studentEnrollment.programTerm.program'])
            ->orderByDesc('generated_at')->orderByDesc('id')->paginate(30);
        $history = DB::table('placement_judgments')->whereIn('recommendation_id', $records->pluck('id'))
            ->orderByDesc('id')->get(['id', 'recommendation_id', 'judgment', 'reason', 'approved_at'])->groupBy('recommendation_id');
        $records->through(fn ($record) => [
            'id' => $record->id, 'student_name' => $record->studentEnrollment->student->user->name,
            'program' => $record->studentEnrollment->programTerm->program->code,
            'explanation' => RecommendationReview::explanation($record),
            'history' => $history->get($record->id, collect())->values(),
        ]);

        return response()->json($records, headers: ['Cache-Control' => 'no-store']);
    }

    public function store(Request $request, int $recommendation)
    {
        abort_unless($request->user()->role === Role::Coordinator, 403);
        $record = RecommendationReview::query($request->user())->findOrFail($recommendation);
        $id = RecommendationReview::record($request->user(), $record, $request->all());

        return response()->json(['data' => ['id' => $id]], 201);
    }
}
