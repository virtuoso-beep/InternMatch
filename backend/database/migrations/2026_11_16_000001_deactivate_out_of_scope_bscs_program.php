<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BSCS is outside the approved program scope (BSIT, BSCRIM, BSA; confirmed 2026-11-16).
 * Deactivate, never delete: enrollments, coordinator links and history are preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('programs')->where('code', 'BSCS')->where('is_active', true)->update(['is_active' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('programs')->where('code', 'BSCS')->update(['is_active' => true, 'updated_at' => now()]);
    }
};
