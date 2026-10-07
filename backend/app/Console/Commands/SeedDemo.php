<?php

namespace App\Console\Commands;

use App\Models\Opportunity;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\GenerateRecommendations;
use App\Services\SemanticEmbeddings;
use Database\Seeders\SyntheticDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedDemo extends Command
{
    protected $signature = 'internmatch:seed-demo {--recommendations : Generate real E5 recommendations for the synthetic cohort}';

    protected $description = 'Add persistent, fictional local testing data without resetting existing records';

    public function handle(SemanticEmbeddings $embeddings, GenerateRecommendations $matching): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Synthetic data is restricted to local/testing environments.');

            return self::FAILURE;
        }
        if ($this->call('db:seed', ['--class' => SyntheticDemoSeeder::class]) !== self::SUCCESS) {
            return self::FAILURE;
        }
        if ($this->option('recommendations')) {
            $enrollments = StudentEnrollment::whereHas('programTerm.academicTerm', fn ($q) => $q->where('code', SyntheticDemoSeeder::TERM))->whereHas('student.user', fn ($q) => $q->where('email', 'student@example.com'))->get();
            foreach (Opportunity::whereHas('academicTerm', fn ($q) => $q->where('code', SyntheticDemoSeeder::TERM))->get() as $opportunity) {
                $embeddings->refresh($opportunity);
            }
            $actor = User::where('email', 'coordinator@example.com')->firstOrFail();
            foreach ($enrollments as $enrollment) {
                $embeddings->refresh($enrollment->student);
                if (! DB::table('recommendation_generations')->where('student_enrollment_id', $enrollment->id)->exists()) {
                    $matching->generate($actor, $enrollment);
                }
            }
        }
        $this->info('Synthetic records ready. See docs/SYNTHETIC_TEST_DATA.md for logins and scenarios.');

        return self::SUCCESS;
    }
}
