<?php

namespace App\Filament\Resources\Opportunities;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\Competency;
use App\Models\Opportunity;
use App\Models\Program;
use App\Services\HostAccess;
use App\Services\OpportunityManagement;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->canAccessPanel(Filament::getCurrentOrDefaultPanel()) ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny() && Filament::auth()->user()->hasPermission(Permission::ManageHosts);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::canViewAny() ? OpportunityManagement::query(Filament::auth()->user())->with(['hostEstablishment', 'academicTerm', 'programs']) : parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('host_establishment_id')->label('Host')->required()->disabledOn('edit')->dehydrated()
                ->options(fn () => HostAccess::query(Filament::auth()->user())->orderBy('name')->pluck('name', 'id')),
            Select::make('academic_term_id')->label('Academic term')->required()->disabledOn('edit')->dehydrated()
                ->options(fn () => AcademicTerm::orderByDesc('starts_on')->pluck('name', 'id')),
            TextInput::make('title')->required()->maxLength(255),
            Textarea::make('description')->required()->maxLength(10000),
            Textarea::make('tasks')->required()->maxLength(10000),
            Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'])->default('draft')->required(),
            DatePicker::make('starts_on'), DatePicker::make('ends_on')->afterOrEqual('starts_on'),
            Select::make('competency_ids')->label('Required competencies')->multiple()->searchable()
                ->options(fn () => Competency::orderBy('name')->pluck('name', 'id'))->default([]),
            Repeater::make('programs')->label('Slots by eligible program')->required()->minItems(1)->maxItems(100)->reorderable(false)
                ->helperText('Each program needs host capacity for this term. Existing placements must retain their slots.')
                ->schema([
                    Select::make('program_id')->label('Program')->required()->options(function () {
                        $query = Program::query();
                        $user = Filament::auth()->user();
                        if ($user->role === Role::Coordinator) {
                            $query->whereIn('id', $user->programs()->select('programs.id'));
                        }

                        return $query->orderBy('code')->pluck('name', 'id');
                    }),
                    TextInput::make('capacity')->label('Slots')->integer()->required()->minValue(0)->maxValue(100000),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->recordUrl(fn (Opportunity $record) => Gate::allows('update', $record) ? static::getUrl('edit', ['record' => $record]) : null)
            ->columns([
                TextColumn::make('title')->searchable(), TextColumn::make('hostEstablishment.name')->label('Host')->searchable(),
                TextColumn::make('academicTerm.name')->label('Term'), TextColumn::make('programs.code')->label('Programs')->listWithLineBreaks(),
                TextColumn::make('status')->badge(),
            ])->recordActions([EditAction::make()])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListOpportunities::route('/'), 'create' => Pages\CreateOpportunity::route('/create'), 'edit' => Pages\EditOpportunity::route('/{record}/edit')];
    }
}
