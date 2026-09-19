<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requirement_submissions', fn (Blueprint $table) => $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected'])->default('draft')->change());
    }

    public function down(): void
    {
        DB::table('requirement_submissions')->where('status', 'under_review')->update(['status' => 'submitted']);
        Schema::table('requirement_submissions', fn (Blueprint $table) => $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft')->change());
    }
};
