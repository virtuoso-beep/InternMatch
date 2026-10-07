<?php

namespace App\Filament\Resources\Allocations\Pages;

use App\Filament\Resources\Allocations\AllocationResource;
use App\Models\ProgramTerm;
use App\Services\CohortAllocation;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListAllocations extends ListRecords
{
    protected static string $resource = AllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('generate')->label('Generate cohort proposal')->schema([
            Select::make('program_term_id')->label('Cohort')->required()->options(fn () => ProgramTerm::whereIn('program_id', Filament::auth()->user()->programs()->select('programs.id'))->with(['program', 'academicTerm'])->get()->mapWithKeys(fn ($t) => [$t->id => $t->program->code.' / '.$t->academicTerm->name])),
        ])->action(fn (array $data) => app(CohortAllocation::class)->generate(Filament::auth()->user(), ProgramTerm::findOrFail($data['program_term_id'])))
            ->successNotificationTitle('Proposal saved for review')];
    }
}
