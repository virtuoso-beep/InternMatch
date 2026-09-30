<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->boolean('academic_eligibility_confirmed')->nullable();
            $table->foreignId('eligibility_confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('eligibility_confirmed_at')->nullable();
            $table->text('eligibility_note')->nullable();
        });
        Schema::table('placement_proposal_items', function (Blueprint $table) {
            $table->enum('review_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('review_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('placement_proposal_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['review_status', 'review_reason', 'reviewed_at']);
        });
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('eligibility_confirmed_by');
            $table->dropColumn(['academic_eligibility_confirmed', 'eligibility_confirmed_at', 'eligibility_note']);
        });
    }
};
