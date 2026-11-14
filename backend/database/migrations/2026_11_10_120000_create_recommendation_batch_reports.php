<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('recommendation_batch_reports', function(Blueprint $table){$table->id();$table->foreignId('user_id')->constrained();$table->foreignId('program_id')->constrained();$table->json('results');$table->timestamp('created_at');}); }
 public function down(): void {Schema::dropIfExists('recommendation_batch_reports');}
};
