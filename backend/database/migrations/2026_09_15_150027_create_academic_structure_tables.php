<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('academic_year', 30);
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_active')->default(true);
            $table->index(['academic_year', 'is_active']);
            $table->timestamps();
        });

        Schema::create('program_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_term_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('required_minutes');
            $table->json('monitoring_rules')->nullable();
            $table->unique(['program_id', 'academic_term_id']);
            $table->timestamps();
        });

        Schema::create('program_term_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['program_term_id', 'user_id']);
            $table->timestamps();
        });

        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('contact_number', 40)->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_disk')->nullable();
            $table->string('avatar_path')->nullable();
            $table->boolean('notify_email')->default(true);
            $table->boolean('notify_digest')->default(false);
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('student_number', 50)->unique();
            $table->timestamps();
        });

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_term_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('year_level')->nullable();
            $table->unsignedInteger('required_minutes');
            $table->enum('status', ['enrolled', 'withdrawn', 'completed'])->default('enrolled');
            $table->date('enrolled_on');
            $table->date('target_completion_on')->nullable();
            $table->unique(['student_id', 'program_term_id']);
            $table->unique(['id', 'program_term_id']);
            $table->index(['program_term_id', 'status']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('students');
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('program_term_user');
        Schema::dropIfExists('program_terms');
        Schema::dropIfExists('academic_terms');
        Schema::dropIfExists('programs');
    }
};
