<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requirement_types', fn (Blueprint $table) => $table->enum('kind', ['document', 'event'])->default('document'));
        Schema::table('program_term_requirements', fn (Blueprint $table) => $table->date('scheduled_on')->nullable());
        Schema::create('program_requirement_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('requirement_type_id')->constrained()->restrictOnDelete();
            $table->boolean('is_required')->default(true);
            $table->boolean('required_before_deployment')->default(true);
            $table->unique(['program_id', 'requirement_type_id'], 'program_requirement_template_unique');
        });
        Schema::create('event_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id');
            $table->foreignId('program_term_requirement_id');
            $table->foreignId('program_term_id')->constrained()->restrictOnDelete();
            $table->date('attended_on');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreign(['student_enrollment_id', 'program_term_id'], 'attendance_enrollment_term_fk')->references(['id', 'program_term_id'])->on('student_enrollments')->restrictOnDelete();
            $table->foreign(['program_term_requirement_id', 'program_term_id'], 'attendance_requirement_term_fk')->references(['id', 'program_term_id'])->on('program_term_requirements')->restrictOnDelete();
            $table->unique(['student_enrollment_id', 'program_term_requirement_id'], 'attendance_student_requirement_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendances');
        Schema::dropIfExists('program_requirement_templates');
        Schema::table('program_term_requirements', fn (Blueprint $table) => $table->dropColumn('scheduled_on'));
        Schema::table('requirement_types', fn (Blueprint $table) => $table->dropColumn('kind'));
    }
};
