<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
    public function up(): void {
        Schema::create('system_preferences', function(Blueprint $table){$table->id();$table->string('system_name',80);$table->string('institution',160);$table->string('display_timezone',50);$table->timestamp('updated_at')->nullable();});
        DB::table('system_preferences')->insert(['id'=>1,'system_name'=>'InternMatch','institution'=>'University of Mindanao Tagum College','display_timezone'=>'Asia/Manila','updated_at'=>now()]);
    }
    public function down(): void {Schema::dropIfExists('system_preferences');}
};
