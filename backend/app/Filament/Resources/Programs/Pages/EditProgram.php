<?php

namespace App\Filament\Resources\Programs\Pages;

use App\Filament\Resources\Programs\ProgramResource;
use App\Services\ProgramSettings;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProgram extends EditRecord
{
    protected static string $resource = ProgramResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return ProgramSettings::update(Filament::auth()->user(), $record, $data);
    }
}
