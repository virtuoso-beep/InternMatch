<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Synthetic test data support (additive only).
 *  - is_synthetic + source_id on every table that can receive imported synthetic rows, so synthetic
 *    records can be filtered out of dashboards, reports and training, and removed later.
 *  - Tables for synthetic sheets that have no existing home (coordinator labels, monitoring check-ins,
 *    absences, the permission reference sheet) and an untouched copy of every source row.
 *  - Two new status values used by the data set (placement "incomplete", weekly log "not_submitted"),
 *    an optional competency level, and the exact source hours on time logs.
 */
return new class extends Migration
{
    private const FLAGGED = [
        'users', 'students', 'student_enrollments', 'academic_terms', 'program_terms', 'competencies',
        'host_establishments', 'moas', 'opportunities', 'placements', 'time_logs', 'journal_entries',
        'evaluation_rubrics', 'evaluations', 'notifications', 'conversations', 'chat_messages', 'audit_logs',
    ];

    public function up(): void
    {
        foreach (self::FLAGGED as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->boolean('is_synthetic')->default(false)->index();
                $table->string('source_id', 40)->nullable();
            });
        }

        Schema::table('generated_reports', fn (Blueprint $table) => $table->boolean('contains_synthetic')->default(false));
        Schema::table('time_logs', fn (Blueprint $table) => $table->decimal('hours', 6, 2)->nullable());
        Schema::table('student_competencies', fn (Blueprint $table) => $table->unsignedTinyInteger('level')->nullable()->change());

        DB::statement("ALTER TABLE placements MODIFY status ENUM('pending','approved','active','completed','rejected','cancelled','incomplete') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE journal_entries MODIFY status ENUM('draft','submitted','approved','rejected','not_submitted') NOT NULL DEFAULT 'draft'");

        Schema::create('synthetic_source_rows', function (Blueprint $table) {
            $table->id();
            $table->string('workbook', 120);
            $table->string('sheet', 60);
            $table->string('source_id', 40);
            $table->string('table_name', 60)->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('values');
            $table->timestamp('created_at')->nullable();
            $table->unique(['sheet', 'source_id']);
        });

        Schema::create('coordinator_labels', function (Blueprint $table) {
            $table->id();
            $table->string('source_id', 40)->nullable()->unique();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained();
            $table->string('hte_code', 50);
            $table->string('opportunity_code', 50);
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('program_match');
            $table->decimal('skill_match_score', 6, 4);
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->unsignedInteger('available_slots');
            $table->boolean('has_slot');
            $table->boolean('moa_valid');
            $table->string('coordinator_decision', 30);
            $table->boolean('suitable')->nullable()->comment('NULL = For Review; excluded from training');
            $table->text('decision_reason')->nullable();
            $table->foreignId('labeled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_synthetic')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('monitoring_records', function (Blueprint $table) {
            $table->id();
            $table->string('source_id', 40)->nullable()->unique();
            $table->foreignId('placement_id')->constrained()->cascadeOnDelete();
            $table->date('monitoring_date');
            $table->decimal('hours_rendered', 7, 2);
            $table->unsignedInteger('days_absent');
            $table->string('attendance_status', 40);
            $table->unsignedInteger('weekly_logs_submitted');
            $table->string('accomplishment_report', 40);
            $table->boolean('monitoring_visit');
            $table->text('issues_concerns')->nullable();
            $table->string('monitoring_status', 40);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_synthetic')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('attendance_absences', function (Blueprint $table) {
            $table->id();
            $table->string('source_id', 40)->nullable()->unique();
            $table->foreignId('placement_id')->constrained()->cascadeOnDelete();
            $table->date('absent_on');
            $table->boolean('is_synthetic')->default(false)->index();
            $table->timestamps();
            $table->unique(['placement_id', 'absent_on']);
        });

        Schema::create('role_permission_references', function (Blueprint $table) {
            $table->id();
            $table->string('source_id', 40)->nullable()->unique();
            $table->string('permission');
            foreach (['admin', 'dean', 'coordinator', 'supervisor', 'student'] as $role) {
                $table->boolean($role);
            }
            $table->boolean('is_synthetic')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission_references');
        Schema::dropIfExists('attendance_absences');
        Schema::dropIfExists('monitoring_records');
        Schema::dropIfExists('coordinator_labels');
        Schema::dropIfExists('synthetic_source_rows');
        DB::statement("ALTER TABLE journal_entries MODIFY status ENUM('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft'");
        DB::statement("ALTER TABLE placements MODIFY status ENUM('pending','approved','active','completed','rejected','cancelled') NOT NULL DEFAULT 'pending'");
        Schema::table('student_competencies', fn (Blueprint $table) => $table->unsignedTinyInteger('level')->nullable(false)->change());
        Schema::table('time_logs', fn (Blueprint $table) => $table->dropColumn('hours'));
        Schema::table('generated_reports', fn (Blueprint $table) => $table->dropColumn('contains_synthetic'));
        foreach (self::FLAGGED as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->dropIndex($name.'_is_synthetic_index');
                $table->dropColumn(['is_synthetic', 'source_id']);
            });
        }
    }
};
