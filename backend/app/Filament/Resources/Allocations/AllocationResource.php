<?php

namespace App\Filament\Resources\Allocations;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Opportunity;
use App\Models\PlacementProposalItem;
use App\Models\User;
use App\Services\CohortAllocation;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AllocationResource extends Resource
{
    protected static ?string $model = PlacementProposalItem::class;

    protected static ?string $slug = 'allocations';

    protected static ?string $navigationLabel = 'Allocation review';

    protected static ?string $modelLabel = 'allocation item';

    public static function canViewAny(): bool
    {
        $actor = Filament::auth()->user();

        return $actor?->role === Role::Coordinator && $actor->hasPermission(Permission::DecidePlacements);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->when(static::canViewAny(),
            fn ($q) => $q->whereHas('programTerm', fn ($t) => $t->whereIn('program_id', Filament::auth()->user()->programs()->select('programs.id'))),
            fn ($q) => $q->whereRaw('1 = 0'))->with(['programTerm.program', 'studentEnrollment.student.user', 'opportunity.hostEstablishment']);
    }

    public static function table(Table $table): Table
    {
        return $table->recordUrl(null)->columns([
            TextColumn::make('placement_proposal_id')->label('Proposal')->sortable(),
            TextColumn::make('studentEnrollment.student.user.name')->label('Student')->searchable(),
            TextColumn::make('programTerm.program.code')->label('Program'),
            TextColumn::make('opportunity.title')->label('Proposed opportunity')->placeholder('Unassigned'),
            TextColumn::make('reason')->wrap()->limit(150),
            TextColumn::make('review_status')->badge(),
            TextColumn::make('review_reason')->wrap(),
        ])->recordActions([
            Action::make('decide')->label('Placement decision')->visible(fn (PlacementProposalItem $record) => $record->review_status === 'pending')
                ->modalDescription('Review the proposal, select the proposed opportunity or an override, and give a reason. Approval rechecks readiness, MOA and current capacity; it creates the final placement.')
                ->fillForm(fn (PlacementProposalItem $record) => ['opportunity_id' => $record->opportunity_id, 'facts' => $record->reason])
                ->schema([
                    Textarea::make('facts')->label('Proposal explanation')->disabled()->dehydrated(false),
                    Select::make('decision')->options(['approve' => 'Approve placement', 'reject' => 'Reject proposal'])->required()->live(),
                    Select::make('opportunity_id')->label('Opportunity or override')->searchable()->options(fn (PlacementProposalItem $record) => Opportunity::where('academic_term_id', $record->programTerm->academic_term_id)->where('status', 'published')->whereHas('programs', fn ($q) => $q->whereKey($record->programTerm->program_id))->pluck('title', 'id')),
                    Select::make('supervisor_id')->label('Assigned supervisor')->searchable()->options(fn (PlacementProposalItem $record) => User::where('role', Role::Supervisor)->where('status', 'active')->whereHas('hostEstablishments.opportunities.programs', fn ($q) => $q->whereKey($record->programTerm->program_id))->pluck('name', 'id')),
                    DatePicker::make('starts_on')->label('Placement start'), DatePicker::make('ends_on')->label('Placement end'),
                    Textarea::make('reason')->minLength(5)->maxLength(2000)->required(),
                ])->action(fn (PlacementProposalItem $record, array $data) => app(CohortAllocation::class)->decide(Filament::auth()->user(), $record->id, $data))
                ->successNotificationTitle('Placement decision saved'),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAllocations::route('/')];
    }
}
