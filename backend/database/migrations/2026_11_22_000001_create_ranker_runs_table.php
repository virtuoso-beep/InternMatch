<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** History of "Update recommendations" runs (spec §9): when, which judgments, and the result. Additive. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranker_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 20); // admin (button) or command (php artisan ml:retrain)
            $table->boolean('used_test_data')->default(false);
            $table->unsignedInteger('suitable')->default(0);
            $table->unsignedInteger('not_suitable')->default(0);
            $table->unsignedInteger('uncertain')->default(0);
            $table->unsignedInteger('students')->default(0);
            $table->string('result', 30); // updated, not_enough_data, not_reliable, failed
            $table->text('reason')->nullable();
            $table->json('metrics')->nullable();
            $table->json('fallback')->nullable();
            $table->timestamp('model_trained_at')->nullable();
            $table->string('model_sha256', 64)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['result', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranker_runs');
    }
};
