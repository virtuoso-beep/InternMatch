<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Services\HostAccess;
use App\Services\HostManagement;
use Illuminate\Http\Request;

class HostController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(in_array($request->user()->role, [Role::Admin, Role::Coordinator, Role::Supervisor], true), 403);

        return response()->json(HostAccess::query($request->user())->orderBy('name')->paginate(50), headers: ['Cache-Control' => 'no-store']);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => HostManagement::create($request->user(), $request->all())], 201);
    }

    public function update(Request $request, int $host)
    {
        $record = HostAccess::query($request->user())->findOrFail($host);

        return response()->json(['data' => HostManagement::update($request->user(), $record, $request->all())]);
    }

    public function capacities(Request $request, int $host)
    {
        $record = HostAccess::query($request->user())->findOrFail($host);

        return response()->json(['data' => HostManagement::capacities($request->user(), $record)]);
    }

    public function updateCapacity(Request $request, int $host)
    {
        $record = HostAccess::query($request->user())->findOrFail($host);
        HostManagement::updateCapacity($request->user(), $record, $request->all());

        return $this->capacities($request, $host);
    }
}
