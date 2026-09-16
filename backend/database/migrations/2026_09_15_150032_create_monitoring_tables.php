<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained()->restrictOnDelete();
            $table->date('work_date');
            $table->dateTime('time_in');
            $table->dateTime('time_out')->nullable();
            $table->unsignedInteger('break_minutes')->default(0);
            $table->unsignedInteger('credited_minutes')->nullable();
            $table->enum('status', ['pending', 'verified', 'flagged'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->unique(['placement_id', 'time_in']);
            $table->index(['placement_id', 'work_date', 'status']);
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained()->restrictOnDelete();
            $table->date('week_starts_on');
            $table->text('content');
            $table->foreignId('document_id')->nullable()->constrained()->restrictOnDelete();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->unique(['placement_id', 'week_starts_on']);
            $table->timestamps();
        });

        Schema::create('monitoring_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained()->restrictOnDelete();
            $table->string('code', 80);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->text('description');
            $table->foreignId('raised_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('raised_at');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution')->nullable();
            $table->index(['placement_id', 'resolved_at']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_flags');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('time_logs');
    }
};
