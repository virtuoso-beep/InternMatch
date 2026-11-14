<?php

namespace Database\Seeders;

use App\Models\Competency;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        // Starter vocabulary, not an approved curriculum or evidence of student attainment.
        $catalog = [
            ['BSA', 'Bachelor of Science in Accountancy', 'DAE - Accounting Education', 'Financial Accounting|Auditing|Bookkeeping|Taxation|Cost Accounting|Financial Reporting|Bank Reconciliation|Accounts Payable|Accounts Receivable|Payroll Accounting|Internal Controls|Accounting Information Systems|Spreadsheet Analysis|Budgeting|Professional Ethics'],
            ['BSCS', 'Bachelor of Science in Computer Science', 'DCE - Computing Education', 'Programming|Algorithms|Data Structures|Database Design|Software Engineering|Software Testing|Web Development|Machine Learning Fundamentals|Data Analysis|Version Control|Operating Systems|Computer Networks|Technical Documentation|Problem Solving|REST APIs'],
            ['BSIT', 'Bachelor of Science in Information Technology', 'DCE - Computing Education', 'Web Development|Programming|Database Management|Networking'],
            ['BSCRIM', 'Bachelor of Science in Criminology', 'DCJE - Criminal Justice Education', 'Law Enforcement Procedures|Investigation Support|Evidence Documentation|Forensic Awareness|Crime Prevention|Incident Reporting|Community Relations|Criminal Law Fundamentals|Public Safety|Research Methods|Interviewing|Records Management|Professional Ethics|Observation Skills|Report Writing'],
        ];
        foreach ($catalog as [$code, $name, $cluster, $vocabulary]) {
            // BSIT: 486 hours confirmed by the user against the department-approved data guide on 2026-09-24.
            // BSCS: 350 hours explicitly confirmed by the user on 2026-11-02.
            // Existing program settings are preserved; BSA hours are supplied by its source import.
            $program = Program::firstOrCreate(['code' => $code], ['name' => $name, 'cluster' => $cluster,
                'required_ojt_hours' => match ($code) { 'BSIT' => 486, 'BSCS' => 350, default => null }]);
            if ($program->cluster === null) {
                $program->update(['cluster' => $cluster]);
            }
            foreach (explode('|', $vocabulary) as $label) {
                $competency = Competency::firstOrCreate(['code' => Str::slug($label)], ['name' => $label]);
                $program->competencies()->syncWithoutDetaching([$competency->id]);
            }
        }
    }
}
