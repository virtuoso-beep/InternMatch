<?php

namespace App\Http\Controllers;

use App\Services\StudentAccess;
use App\Services\StudentEnrollmentManagement;
use Illuminate\Http\Request;

class StudentManagementController extends Controller
{
    public function withdraw(Request $request, int $enrollment)
    {
        $record = StudentAccess::enrollments($request->user())->findOrFail($enrollment);

        return response()->json(['data' => StudentEnrollmentManagement::withdraw($request->user(), $record, $request->all())]);
    }

    public function update(Request $request, int $enrollment)
    {
        $record = StudentAccess::enrollments($request->user())->findOrFail($enrollment);

        return response()->json(['data' => StudentEnrollmentManagement::update($request->user(), $record, $request->all())]);
    }
}
