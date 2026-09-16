<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_rubrics', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60);
            $table->unsignedInteger('version')->default(1);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unique(['code', 'version']);
            $table->timestamps();
        });

        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_rubric_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->decimal('max_score', 8, 2);
            $table->decimal('weight', 8, 4)->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unique(['evaluation_rubric_id', 'name']);
            $table->unique(['id', 'evaluation_rubric_id']);
            $table->timestamps();
        });

        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained()->restrictOnDelete();
            $table->foreignId('evaluation_rubric_id')->constrained()->restrictOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->restrictOnDelete();
            $table->string('period', 60);
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->text('comments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unique(['placement_id', 'period']);
            $table->unique(['id', 'evaluation_rubric_id']);
            $table->timestamps();
        });

        Schema::create('evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id');
            $table->foreignId('evaluation_criterion_id');
            $table->foreignId('evaluation_rubric_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 8, 2);
            $table->text('comments')->nullable();
            $table->foreign(['evaluation_id', 'evaluation_rubric_id'], 'score_evaluation_rubric_fk')->references(['id', 'evaluation_rubric_id'])->on('evaluations')->restrictOnDelete();
            $table->foreign(['evaluation_criterion_id', 'evaluation_rubric_id'], 'score_criterion_rubric_fk')->references(['id', 'evaluation_rubric_id'])->on('evaluation_criteria')->restrictOnDelete();
            $table->unique(['evaluation_id', 'evaluation_criterion_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_scores');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('evaluation_criteria');
        Schema::dropIfExists('evaluation_rubrics');
    }
};
