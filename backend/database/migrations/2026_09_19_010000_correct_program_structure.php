<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('cluster')->nullable();
            // No department-approved hours were supplied. NULL must never mean zero hours.
            $table->unsignedInteger('required_ojt_hours')->nullable();
            $table->string('internship_term')->nullable();
            $table->foreignId('evaluation_rubric_id')->nullable()->constrained()->restrictOnDelete();
        });
        DB::statement('ALTER TABLE programs ADD CONSTRAINT program_hours_positive CHECK (required_ojt_hours IS NULL OR required_ojt_hours > 0)');

        Schema::create('coordinator_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'program_id']);
            $table->timestamps();
        });
        // Preserve the existing program assignments, while removing term-specific access semantics.
        $assignments = DB::table('program_term_user as assignment')
            ->join('program_terms as term', 'term.id', '=', 'assignment.program_term_id')
            ->join('users', 'users.id', '=', 'assignment.user_id')
            ->whereIn('users.role', ['coordinator', 'dean'])
            ->select('assignment.user_id', 'term.program_id')->distinct()->get();
        foreach ($assignments as $assignment) {
            DB::table('coordinator_program')->insert([
                'user_id' => $assignment->user_id, 'program_id' => $assignment->program_id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::create('program_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->restrictOnDelete();
            $table->unique(['program_id', 'competency_id']);
            $table->timestamps();
        });
        Schema::table('opportunity_program', function (Blueprint $table) {
            // Existing shared capacity cannot be safely divided without host confirmation.
            $table->unsignedInteger('capacity')->nullable();
        });
        Schema::create('host_program_capacity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_establishment_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_term_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('capacity');
            $table->unique(['host_establishment_id', 'program_id', 'academic_term_id'], 'host_program_term_capacity_unique');
            $table->timestamps();
        });
        // Retain the existing many-program coverage relation. Empty coverage is NOT institution-wide.
        Schema::table('moas', function (Blueprint $table) {
            $table->boolean('is_institution_wide')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('moas', fn (Blueprint $table) => $table->dropColumn('is_institution_wide'));
        Schema::dropIfExists('host_program_capacity');
        Schema::table('opportunity_program', fn (Blueprint $table) => $table->dropColumn('capacity'));
        Schema::dropIfExists('program_competencies');
        Schema::dropIfExists('coordinator_program');
        DB::statement('ALTER TABLE programs DROP CHECK program_hours_positive');
        Schema::table('programs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evaluation_rubric_id');
            $table->dropColumn(['cluster', 'required_ojt_hours', 'internship_term']);
        });
    }
};
