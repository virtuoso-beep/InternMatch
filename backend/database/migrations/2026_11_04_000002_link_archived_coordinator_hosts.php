<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Link only hosts whose source-local code AND name match the archived BSA workbook.
        $program = DB::table('programs')->where('code', 'BSA')->value('id');
        if (! $program) return;
        foreach (DB::table('coordinator_source_rows')->where('program_id', $program)->where('sheet', 'HTE Records')->get() as $source) {
            $values = json_decode($source->values, true);
            if (! isset($values[0], $values[1])) continue;
            $id = DB::table('host_establishments')->where('code', 'DAE-'.$values[0])->where('name', $values[1])->value('id');
            if ($id) DB::table('import_host_program')->insertOrIgnore(['host_establishment_id' => $id, 'program_id' => $program]);
        }
    }

    public function down(): void
    {
        // Preserve established program links on rollback.
    }
};
