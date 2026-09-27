<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\RequirementType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InternMatchReferenceSeeder extends Seeder
{
    public const DOCUMENTS = [
        'due-diligence' => 'Due Diligence (Notarized)',
        'medical-certificate' => 'Medical Certificate',
        'neuro-exam' => 'Neuro Exam',
        'barangay-clearance' => 'Barangay Clearance',
        'cedula' => 'Cedula',
        'police-clearance' => 'Police Clearance',
        'nbi-clearance' => 'NBI Clearance',
        'drug-test' => 'Drug Test',
        'good-moral-certificate' => 'Good Moral Certificate',
        'resume-application-letter' => 'Resume / Application Letter',
    ];

    public const EVENTS = [
        'pdos' => 'Pre-Deployment Orientation Seminar (PDOS)',
        'anti-sexual-harassment-seminar' => 'Anti-Sexual Harassment Seminar',
        'work-ethics-seminar' => 'Work Ethics Seminar',
        'pinning-ceremony' => 'Pinning Ceremony',
    ];

    public function run(): void
    {
        $program = Program::where('code', 'BSIT')->firstOrFail();
        foreach (['document' => self::DOCUMENTS, 'event' => self::EVENTS] as $kind => $catalog) {
            foreach ($catalog as $code => $name) {
                $type = RequirementType::firstOrCreate(['code' => $code], ['name' => $name, 'kind' => $kind]);
                DB::table('program_requirement_templates')->insertOrIgnore([
                    'program_id' => $program->id, 'requirement_type_id' => $type->id,
                    'is_required' => true, 'required_before_deployment' => true,
                ]);
            }
        }
        // Hosts may request this separately; it is not in the 14 BSIT requirements.
        RequirementType::firstOrCreate(['code' => 'endorsement-letter'], ['name' => 'Endorsement Letter', 'kind' => 'document']);
    }
}
