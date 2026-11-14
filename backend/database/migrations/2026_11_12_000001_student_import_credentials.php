<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->boolean('must_change_password')->default(false);
        });
        Schema::create('import_credential_handoffs', function (Blueprint $table) {
            $table->uuid('import_id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->longText('encrypted_credentials')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('import_credential_handoffs');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('must_change_password'));
        // Retain nullable email: reversing must not destroy email-free student accounts.
    }
};
