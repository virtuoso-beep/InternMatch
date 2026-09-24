<?php

namespace App\Filament\Resources\Opportunities\Pages;

use App\Filament\Resources\Opportunities\OpportunityResource;
use App\Services\OpportunityManagement;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditOpportunity extends EditRecord
{
    protected static string $resource = OpportunityResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['capacity']);
        $data['programs'] = $this->getRecord()->programs->map(fn ($program) => ['program_id' => $program->id, 'capacity' => $program->pivot->capacity])->all();
        $data['competency_ids'] = $this->getRecord()->competencies()->pluck('competencies.id')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return OpportunityManagement::save(Filament::auth()->user(), $data, $record);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(collect($exception->errors())->mapWithKeys(fn ($messages, $field) => ['data.'.$field => $messages])->all());
        }
    }
}
