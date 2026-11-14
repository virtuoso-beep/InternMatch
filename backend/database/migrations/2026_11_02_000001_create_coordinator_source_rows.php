<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coordinator_source_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->string('source_name');
            $table->string('source_sha256', 64);
            $table->string('sheet', 80);
            $table->unsignedInteger('source_row');
            $table->json('values');
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->unique(['source_sha256', 'sheet', 'source_row'], 'coordinator_source_row_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coordinator_source_rows');
    }
};
