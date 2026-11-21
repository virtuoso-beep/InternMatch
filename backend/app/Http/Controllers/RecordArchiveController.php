<?php

namespace App\Http\Controllers;

use App\Services\RecordArchive;
use Illuminate\Http\Request;

/** Archive, restore or delete (never-used only) hosts, MOAs, opportunities and academic terms. */
class RecordArchiveController extends Controller
{
    private const TYPES = ['hosts' => 'host', 'moas' => 'moa', 'opportunities' => 'opportunity', 'academic-terms' => 'term'];

    public function archive(Request $request, string $type, int $id)
    {
        return response()->json(['data' => RecordArchive::archive($request->user(), self::TYPES[$type], $id)]);
    }

    public function restore(Request $request, string $type, int $id)
    {
        return response()->json(['data' => RecordArchive::restore($request->user(), self::TYPES[$type], $id)]);
    }

    public function destroy(Request $request, string $type, int $id)
    {
        RecordArchive::delete($request->user(), self::TYPES[$type], $id);

        return response()->noContent();
    }
}
