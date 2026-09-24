<?php

namespace App\Filament\Resources\Hosts\Pages;

use App\Filament\Resources\Hosts\HostResource;
use App\Services\HostManagement;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditHost extends EditRecord
{
    protected static string $resource = HostResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['capacities'] = array_map(fn ($row) => [...$row, '_existing' => true], HostManagement::capacities(Filament::auth()->user(), $this->getRecord()));

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return DB::transaction(function () use ($record, $data) {
                $actor = Filament::auth()->user();
                $host = HostManagement::update($actor, $record, $data);
                HostManagement::updateCapacity($actor, $host, $data);

                return $host;
            });
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(collect($exception->errors())->mapWithKeys(fn ($messages, $field) => ['data.'.$field => $messages])->all());
        }
    }
}
