<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('competency_program_term', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_term_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('target_level')->nullable();
            $table->unique(['competency_id', 'program_term_id']);
            $table->timestamps();
        });

        Schema::create('opportunity_competency', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->restrictOnDelete();
            $table->foreignId('competency_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('minimum_level')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unique(['opportunity_id', 'competency_id']);
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('disk')->default('local');
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('student_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('competency_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('level');
            $table->timestamp('assessed_at')->nullable();
            $table->unique(['student_id', 'competency_id']);
            $table->timestamps();
        });

        Schema::create('competency_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_competency_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('source', 80);
            $table->text('description')->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->timestamps();
        });
        Schema::table('moas', function (Blueprint $table) {
            $table->foreignId('document_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('moas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_id');
        });
        Schema::dropIfExists('competency_evidence');
        Schema::dropIfExists('student_competencies');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('opportunity_competency');
        Schema::dropIfExists('competency_program_term');
        Schema::dropIfExists('competencies');
    }
};
