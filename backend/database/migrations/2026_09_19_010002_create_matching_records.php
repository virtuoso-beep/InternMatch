<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embeddings', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('source_text_hash', 64);
            $table->string('model_name', 100);
            $table->string('model_version', 100);
            $table->json('vector');
            $table->timestamp('generated_at');
            $table->unique(['owner_type', 'owner_id', 'source_text_hash', 'model_name', 'model_version'], 'embedding_provenance_unique');
        });

        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->uuid('generation_id')->index();
            $table->foreignId('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('opportunity_id')->constrained()->restrictOnDelete();
            $table->double('similarity_score');
            $table->double('distance_km')->nullable();
            $table->unsignedInteger('capacity_at_time');
            $table->string('moa_status_at_time', 40);
            $table->unsignedInteger('rank');
            $table->string('ranking_method', 100);
            // Includes source hashes, model versions, program, host and agreement facts.
            $table->json('snapshot');
            $table->timestamp('generated_at');
            $table->unique(['generation_id', 'student_enrollment_id', 'opportunity_id'], 'recommendation_generation_unique');
            $table->unique(['generation_id', 'student_enrollment_id', 'rank'], 'recommendation_rank_unique');
            $table->unique(['id', 'student_enrollment_id'], 'recommendation_student_unique');
        });
        DB::statement('ALTER TABLE recommendations ADD CONSTRAINT recommendation_values CHECK (similarity_score BETWEEN -1 AND 1 AND (distance_km IS NULL OR distance_km >= 0) AND `rank` > 0)');

        Schema::create('placement_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_term_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['draft', 'under_review', 'approved', 'rejected'])->default('draft');
            $table->json('constraints_snapshot');
            $table->unique(['id', 'program_term_id'], 'proposal_term_unique');
            $table->timestamps();
        });
        Schema::create('placement_proposal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_proposal_id');
            $table->foreignId('program_term_id');
            $table->foreignId('student_enrollment_id');
            $table->foreignId('recommendation_id')->nullable();
            $table->foreignId('opportunity_id')->nullable()->constrained()->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->unique(['placement_proposal_id', 'student_enrollment_id'], 'proposal_student_unique');
            $table->foreign(['placement_proposal_id', 'program_term_id'], 'proposal_item_term_fk')->references(['id', 'program_term_id'])->on('placement_proposals')->restrictOnDelete();
            $table->foreign(['student_enrollment_id', 'program_term_id'], 'proposal_student_term_fk')->references(['id', 'program_term_id'])->on('student_enrollments')->restrictOnDelete();
            $table->foreign(['recommendation_id', 'student_enrollment_id'], 'proposal_recommendation_student_fk')->references(['id', 'student_enrollment_id'])->on('recommendations')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('placement_judgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recommendation_id')->constrained()->restrictOnDelete();
            $table->foreignId('coordinator_id')->constrained('users')->restrictOnDelete();
            $table->enum('judgment', ['suitable', 'unsuitable', 'uncertain']);
            $table->text('reason');
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
        Schema::table('placements', function (Blueprint $table) {
            $table->foreignId('placement_proposal_item_id')->nullable()->constrained()->restrictOnDelete();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::table('placements', fn (Blueprint $table) => $table->dropConstrainedForeignId('placement_proposal_item_id'));
        Schema::dropIfExists('placement_judgments');
        Schema::dropIfExists('placement_proposal_items');
        Schema::dropIfExists('placement_proposals');
        Schema::dropIfExists('recommendations');
        Schema::dropIfExists('embeddings');
    }
};
