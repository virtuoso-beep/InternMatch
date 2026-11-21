<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only.
 * - Archive instead of delete for records with history: hosts, MOAs, opportunities and academic terms.
 * - Coordinator certification of a deployment's hours and the end of the deployment.
 * - The reason a journal was returned, so the student can read it and resubmit.
 */
return new class extends Migration
{
    private const ARCHIVABLE = ['host_establishments', 'moas', 'opportunities', 'academic_terms'];

    public function up(): void
    {
        foreach (self::ARCHIVABLE as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->timestamp('archived_at')->nullable();
                $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
                $table->index('archived_at');
            });
        }
        Schema::table('placements', function (Blueprint $table) {
            $table->timestamp('hours_certified_at')->nullable();
            $table->foreignId('hours_certified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('certified_minutes')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->text('review_comments')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn('review_comments');
        });
        Schema::table('placements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('completed_by');
            $table->dropConstrainedForeignId('hours_certified_by');
            $table->dropColumn(['hours_certified_at', 'certified_minutes']);
        });
        foreach (self::ARCHIVABLE as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['archived_at']);
                $table->dropConstrainedForeignId('archived_by');
                $table->dropColumn('archived_at');
            });
        }
    }
};
