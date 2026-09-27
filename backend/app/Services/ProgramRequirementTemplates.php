<?php

namespace App\Services;

use App\Models\ProgramTerm;
use Illuminate\Support\Facades\DB;

class ProgramRequirementTemplates
{
    public static function apply(ProgramTerm $term): void
    {
        foreach (DB::table('program_requirement_templates')->where('program_id', $term->program_id)->get() as $template) {
            // Preserve term-specific decisions and dates when provisioning later students.
            $term->requirements()->firstOrCreate(['requirement_type_id' => $template->requirement_type_id], [
                'is_required' => $template->is_required,
                'required_before_deployment' => $template->required_before_deployment,
            ]);
        }
    }
}
