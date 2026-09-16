<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('host_establishment_id')->constrained()->restrictOnDelete();
            $table->foreignId('opportunity_id');
            $table->foreignId('moa_id')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->enum('status', ['pending', 'approved', 'active', 'completed', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('current_enrollment_id')->nullable()
                ->storedAs("CASE WHEN status IN ('pending', 'approved', 'active') THEN student_enrollment_id ELSE NULL END");
            $table->unique('current_enrollment_id');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreign(['opportunity_id', 'host_establishment_id'], 'placement_opportunity_host_fk')->references(['id', 'host_establishment_id'])->on('opportunities')->restrictOnDelete();
            $table->foreign(['moa_id', 'host_establishment_id'], 'placement_moa_host_fk')->references(['id', 'host_establishment_id'])->on('moas')->restrictOnDelete();
            $table->index(['host_establishment_id', 'status']);
            $table->index(['supervisor_id', 'status']);
            $table->timestamps();
        });

        Schema::create('placement_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained()->restrictOnDelete();
            $table->foreignId('decided_by')->constrained('users')->restrictOnDelete();
            $table->enum('decision', ['approve', 'reject', 'reassign', 'cancel']);
            $table->foreignId('from_opportunity_id')->nullable()->constrained('opportunities')->restrictOnDelete();
            $table->foreignId('to_opportunity_id')->nullable()->constrained('opportunities')->restrictOnDelete();
            $table->text('reason');
            $table->timestamp('decided_at');
            $table->index(['placement_id', 'decided_at']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_decisions');
        Schema::dropIfExists('placements');
    }
};
