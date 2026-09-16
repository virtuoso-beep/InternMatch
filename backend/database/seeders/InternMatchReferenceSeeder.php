<?php

namespace Database\Seeders;

use App\Models\Competency;
use App\Models\RequirementType;
use Illuminate\Database\Seeder;

class InternMatchReferenceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'web-development' => 'Web Development',
            'database-design' => 'Database Design',
            'rest-apis' => 'REST APIs',
            'networking' => 'Networking',
            'technical-support' => 'Technical Support',
            'ui-design' => 'UI Design',
        ] as $code => $name) {
            Competency::firstOrCreate(['code' => $code], ['name' => $name]);
        }

        foreach ([
            'medical-clearance' => 'Medical Clearance',
            'parental-consent' => 'Parental Consent',
            'endorsement-letter' => 'Endorsement Letter',
            'moa-copy' => 'Memorandum of Agreement Copy',
            'insurance-certificate' => 'Insurance Certificate',
        ] as $code => $name) {
            RequirementType::firstOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
