<?php

namespace App\Http\Controllers;

use App\Services\PlacementCompletion;
use Illuminate\Http\Request;

class PlacementCompletionController extends Controller
{
    public function start(Request $request, int $placement)
    {
        return response()->json(['data' => PlacementCompletion::start($request->user(), $placement)]);
    }

    public function certifyHours(Request $request, int $placement)
    {
        return response()->json(['data' => PlacementCompletion::certifyHours($request->user(), $placement)]);
    }

    public function end(Request $request, int $placement)
    {
        return response()->json(['data' => PlacementCompletion::end($request->user(), $placement)]);
    }
}
