<?php

namespace App\Filament\Resources\Requirements;

use App\Filament\Resources\Requirements\Pages\ListRequirements;
use App\Models\RequirementSubmission;
use App\Services\RequirementReviewService;
use App\Services\StudentAccess;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class RequirementResource extends Resource
{
    protected static ?string $model = RequirementSubmission::class;

    protected static ?string $slug = 'requirements';

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->canAccessPanel(Filament::getCurrentOrDefaultPanel()) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (! static::canViewAny()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('student_enrollment_id', StudentAccess::enrollments(Filament::auth()->user())->select('id'))
            ->with(['studentEnrollment.student.user', 'programTerm.program', 'programTermRequirement.requirementType', 'reviews']);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('studentEnrollment.student.user.name')->label('Student')->searchable(),
            TextColumn::make('programTerm.program.code')->label('Program'),
            TextColumn::make('programTermRequirement.requirementType.name')->label('Requirement'),
            TextColumn::make('revision'),
            TextColumn::make('status')->badge(),
            TextColumn::make('submitted_at')->dateTime()->sortable(),
            TextColumn::make('reviews.comments')->label('Review comments')->wrap(),
        ])->filters([
            SelectFilter::make('status')->options(['submitted' => 'Submitted', 'under_review' => 'Under review', 'approved' => 'Approved', 'rejected' => 'Rejected']),
        ])->recordActions([
            Action::make('download')->label('Download document')
                ->authorize(fn (RequirementSubmission $record) => Gate::allows('view', $record))
                ->url(fn (RequirementSubmission $record) => url('/api/v1/submissions/'.$record->id.'/document')),
            Action::make('review')->authorize(fn (RequirementSubmission $record) => Gate::allows('review', $record))
                ->schema([
                    Select::make('status')->options(['under_review' => 'Under review', 'approved' => 'Approved', 'rejected' => 'Rejected'])->required()->live(),
                    Textarea::make('comments')->maxLength(5000)->required(fn (Get $get) => $get('status') === 'rejected'),
                ])
                ->action(fn (RequirementSubmission $record, array $data) => RequirementReviewService::review(Filament::auth()->user(), $record, $data))
                ->successNotificationTitle('Requirement review saved'),
        ])->defaultSort('submitted_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListRequirements::route('/')];
    }
}
