<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_establishments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name')->index();
            $table->string('industry')->nullable();
            $table->string('address');
            $table->string('city')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_number', 40)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('host_establishment_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_establishment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['host_establishment_id', 'user_id'], 'host_user_unique');
            $table->timestamps();
        });

        Schema::create('moas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_establishment_id')->constrained()->restrictOnDelete();
            $table->string('reference_number', 80)->unique();
            $table->enum('status', ['draft', 'pending_signature', 'active', 'expired', 'terminated'])->default('draft');
            $table->date('effective_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->unsignedInteger('max_interns_per_term')->nullable();
            $table->json('signatories')->nullable();
            $table->text('notes')->nullable();
            $table->unique(['id', 'host_establishment_id']);
            $table->index(['host_establishment_id', 'status', 'expires_on'], 'moas_host_status_expiry_index');
            $table->timestamps();
        });

        Schema::create('moa_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moa_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->unique(['moa_id', 'program_id']);
            $table->timestamps();
        });

        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_establishment_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_term_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description');
            $table->text('tasks')->nullable();
            $table->unsignedInteger('capacity');
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unique(['id', 'host_establishment_id']);
            $table->index(['academic_term_id', 'status']);
            $table->index(['host_establishment_id', 'status']);
            $table->timestamps();
        });

        Schema::create('opportunity_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->unique(['opportunity_id', 'program_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_program');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('moa_program');
        Schema::dropIfExists('moas');
        Schema::dropIfExists('host_establishment_user');
        Schema::dropIfExists('host_establishments');
    }
};
