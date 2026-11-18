<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Spec §2 (17 Nov 2026): coordinator access is a membership with history.
 * Ending an assignment sets removed_at instead of deleting the row, so past access stays on record.
 * A pair may be re-assigned later, but at most one ACTIVE row per (user, program) is allowed
 * (virtual generated column: MySQL forbids STORED ones over cascading foreign-key columns).
 * "One active program per coordinator" depends on the user's role and is enforced in CoordinatorAssignment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coordinator_program', function (Blueprint $table) {
            $table->timestamp('assigned_at')->nullable()->after('program_id');
            $table->timestamp('removed_at')->nullable()->after('assigned_at');
            // Keeps an index for the user_id foreign key once the composite unique key is dropped.
            $table->index('user_id', 'coordinator_program_user_index');
        });
        DB::table('coordinator_program')->whereNull('assigned_at')->update(['assigned_at' => DB::raw('created_at')]);
        Schema::table('coordinator_program', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'program_id']);
        });
        DB::statement("ALTER TABLE coordinator_program ADD COLUMN active_pair VARCHAR(41) GENERATED ALWAYS AS (IF(removed_at IS NULL, CONCAT(user_id, '-', program_id), NULL)) VIRTUAL");
        DB::statement('ALTER TABLE coordinator_program ADD UNIQUE INDEX coordinator_program_active_pair_unique (active_pair)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE coordinator_program DROP INDEX coordinator_program_active_pair_unique');
        DB::statement('ALTER TABLE coordinator_program DROP COLUMN active_pair');
        // Ended rows cannot coexist with the restored unique key.
        DB::table('coordinator_program')->whereNotNull('removed_at')->delete();
        Schema::table('coordinator_program', function (Blueprint $table) {
            $table->unique(['user_id', 'program_id']);
            $table->dropIndex('coordinator_program_user_index');
            $table->dropColumn(['assigned_at', 'removed_at']);
        });
    }
};
