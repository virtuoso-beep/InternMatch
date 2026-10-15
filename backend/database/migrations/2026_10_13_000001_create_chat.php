<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        Schema::create('chat_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
        DB::table('chat_settings')->insert(['id' => 1, 'enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('context_key')->unique();
            $table->foreignId('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('placement_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->restrictOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->uuid('client_id');
            $table->text('body');
            $table->timestamps();
            $table->unique(['sender_id', 'client_id']);
            $table->index(['conversation_id', 'id']);
        });
        Schema::create('conversation_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('last_message_id')->default(0);
            $table->unique(['conversation_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_reads');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('chat_settings');
        Schema::table('users', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
