<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Services\OpportunityManagement;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(in_array($request->user()->role, [Role::Admin, Role::Coordinator, Role::Supervisor], true), 403);

        return response()->json(OpportunityManagement::query($request->user())->with(['programs', 'competencies', 'hostEstablishment'])->orderByDesc('id')->paginate(50));
    }

    public function store(Request $request)
    {
        return response()->json(['data' => OpportunityManagement::save($request->user(), $request->all())], 201);
    }

    public function update(Request $request, int $opportunity)
    {
        $record = OpportunityManagement::query($request->user())->findOrFail($opportunity);

        return response()->json(['data' => OpportunityManagement::save($request->user(), $request->all(), $record)]);
    }
}
