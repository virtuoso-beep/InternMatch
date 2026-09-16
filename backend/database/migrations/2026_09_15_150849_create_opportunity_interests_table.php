<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('opportunity_id')->constrained()->restrictOnDelete();
            $table->timestamp('expressed_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->unique(['student_enrollment_id', 'opportunity_id'], 'enrollment_opportunity_interest_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_interests');
    }
};
