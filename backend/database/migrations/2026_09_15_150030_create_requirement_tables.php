<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirement_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('program_term_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_term_id')->constrained()->restrictOnDelete();
            $table->foreignId('requirement_type_id')->constrained()->restrictOnDelete();
            $table->boolean('is_required')->default(true);
            $table->boolean('required_before_deployment')->default(true);
            $table->dateTime('due_at')->nullable();
            $table->unique(['program_term_id', 'requirement_type_id'], 'term_requirement_unique');
            $table->unique(['id', 'program_term_id']);
            $table->timestamps();
        });

        Schema::create('requirement_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id');
            $table->foreignId('program_term_requirement_id');
            $table->foreignId('program_term_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision')->default(1);
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreign(['student_enrollment_id', 'program_term_id'], 'submission_enrollment_term_fk')->references(['id', 'program_term_id'])->on('student_enrollments')->restrictOnDelete();
            $table->foreign(['program_term_requirement_id', 'program_term_id'], 'submission_requirement_term_fk')->references(['id', 'program_term_id'])->on('program_term_requirements')->restrictOnDelete();
            $table->unique(['student_enrollment_id', 'program_term_requirement_id', 'revision'], 'submission_revision_unique');
            $table->index(['program_term_id', 'status']);
            $table->timestamps();
        });

        Schema::create('requirement_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requirement_submission_id')->constrained()->restrictOnDelete();
            $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->enum('decision', ['approved', 'rejected']);
            $table->text('comments')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirement_reviews');
        Schema::dropIfExists('requirement_submissions');
        Schema::dropIfExists('program_term_requirements');
        Schema::dropIfExists('requirement_types');
    }
};
