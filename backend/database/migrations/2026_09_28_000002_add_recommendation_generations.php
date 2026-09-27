<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('recommendation_generations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('generated_at')->index();
        });
    }
    public function down(): void { Schema::dropIfExists('recommendation_generations'); }
};
