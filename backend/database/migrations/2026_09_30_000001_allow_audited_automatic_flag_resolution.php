<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_flags', fn (Blueprint $table) => $table->boolean('resolved_automatically')->default(false));
        DB::statement('ALTER TABLE monitoring_flags DROP CHECK flag_resolution');
        DB::statement("ALTER TABLE monitoring_flags ADD CONSTRAINT flag_resolution CHECK ((resolved_at IS NULL AND resolved_by IS NULL AND resolved_automatically = 0) OR (resolved_at IS NOT NULL AND ((resolved_by IS NOT NULL AND resolved_automatically = 0) OR (resolved_by IS NULL AND resolved_automatically = 1 AND code LIKE 'auto:%'))))");
    }

    public function down(): void
    {
        if (DB::table('monitoring_flags')->where('resolved_automatically', true)->exists()) {
            throw new RuntimeException('Automatic resolution audit records exist; retain this schema rather than invent a human resolver.');
        }
        DB::statement('ALTER TABLE monitoring_flags DROP CHECK flag_resolution');
        DB::statement('ALTER TABLE monitoring_flags ADD CONSTRAINT flag_resolution CHECK ((resolved_at IS NULL AND resolved_by IS NULL) OR (resolved_at IS NOT NULL AND resolved_by IS NOT NULL))');
        Schema::table('monitoring_flags', fn (Blueprint $table) => $table->dropColumn('resolved_automatically'));
    }
};
