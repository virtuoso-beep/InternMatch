<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Services\StudentEnrollmentManagement;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    public function getHeading(): string
    {
        return 'Edit enrollment — '.$this->getRecord()->student->user->name;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return StudentEnrollmentManagement::update(Filament::auth()->user(), $record, $data);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn ($messages, $field) => ['data.'.$field => $messages])->all()
            );
        }
    }
}
