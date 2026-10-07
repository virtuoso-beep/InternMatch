<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Resources\Reports\ReportResource;
use App\Services\ReportBuilder;
use App\Services\ReportManagement;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('generate')->label('Generate report')->schema([
            Select::make('program_term_id')->label('Cohort')->required()->options(fn () => ReportManagement::terms(Filament::auth()->user())->with(['program', 'academicTerm'])->get()->mapWithKeys(fn ($t) => [$t->id => $t->program->code.' / '.$t->academicTerm->name])),
            Select::make('kind')->options(array_combine(ReportBuilder::KINDS, ReportBuilder::KINDS))->required(),
        ])->action(fn (array $data) => ReportManagement::generate(Filament::auth()->user(), (int) $data['program_term_id'], $data['kind']))->successNotificationTitle('Report snapshot generated')];
    }
}
