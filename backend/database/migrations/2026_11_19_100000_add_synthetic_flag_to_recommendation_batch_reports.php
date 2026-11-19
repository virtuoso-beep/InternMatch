<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recommendation batch reports that include synthetic students are hidden by default and labelled.
 * Existing batches are flagged from the enrollments they reference; their stored results are not changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendation_batch_reports', fn (Blueprint $table) => $table->boolean('contains_synthetic')->default(false)->index());

        $synthetic = DB::table('student_enrollments')->where('is_synthetic', true)->pluck('id')->flip();
        if ($synthetic->isEmpty()) {
            return;
        }
        DB::table('recommendation_batch_reports')->orderBy('id')->each(function ($batch) use ($synthetic) {
            $ids = array_column(json_decode($batch->results, true) ?: [], 'enrollment_id');
            if (collect($ids)->contains(fn ($id) => $synthetic->has($id))) {
                DB::table('recommendation_batch_reports')->where('id', $batch->id)->update(['contains_synthetic' => true]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('recommendation_batch_reports', function (Blueprint $table) {
            $table->dropIndex('recommendation_batch_reports_contains_synthetic_index');
            $table->dropColumn('contains_synthetic');
        });
    }
};
