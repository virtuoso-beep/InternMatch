<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('excel_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('filename');
            $table->json('workbook');
            $table->json('preview')->nullable();
            $table->string('revision', 64)->nullable();
            $table->json('result')->nullable();
            $table->timestamps();
        });
        Schema::create('import_host_program', function (Blueprint $table) {
            $table->foreignId('host_establishment_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->unique(['host_establishment_id', 'program_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('import_host_program');
        Schema::dropIfExists('excel_imports');
    }
};
