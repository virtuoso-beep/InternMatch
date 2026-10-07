<?php

namespace App\Filament\Resources\Reports;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\GeneratedReport;
use App\Services\ReportManagement;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportResource extends Resource
{
    protected static ?string $model = GeneratedReport::class;

    protected static ?string $slug = 'reports';

    protected static ?string $navigationLabel = 'Reports';

    public static function canViewAny(): bool
    {
        $actor = Filament::auth()->user();

        return $actor?->role === Role::Coordinator && $actor->hasPermission(Permission::ViewReports);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return static::canViewAny() ? ReportManagement::query(Filament::auth()->user())->with(['programTerm.program', 'programTerm.academicTerm']) : parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    public static function table(Table $table): Table
    {
        return $table->recordUrl(null)->columns([
            TextColumn::make('id')->label('Report'), TextColumn::make('kind')->badge(),
            TextColumn::make('programTerm.program.code')->label('Program'), TextColumn::make('programTerm.academicTerm.name')->label('Cohort'),
            TextColumn::make('created_at')->dateTime(), TextColumn::make('approved_at')->dateTime()->placeholder('Awaiting review'),
        ])->recordActions([
            Action::make('inspect')->label('View snapshot')->schema([
                Textarea::make('snapshot')->rows(16)->disabled()->dehydrated(false),
            ])->fillForm(fn (GeneratedReport $record) => ['snapshot' => implode(' | ', $record->payload['columns'])."\n".implode("\n", array_map(fn ($row) => implode(' | ', $row), $record->payload['rows']))])->modalSubmitAction(false),
            Action::make('approve')->label('Approve report')->visible(fn (GeneratedReport $record) => ! $record->approved_at)->requiresConfirmation()
                ->action(fn (GeneratedReport $record) => ReportManagement::approve(Filament::auth()->user(), $record->id))->successNotificationTitle('Report approved'),
            Action::make('download')->label('Download CSV')->url(fn (GeneratedReport $record) => url('/api/v1/reports/'.$record->id.'/download')),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListReports::route('/')];
    }
}
