<?php

namespace App\Filament\Resources\Recommendations;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Recommendation;
use App\Services\RecommendationReview;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RecommendationResource extends Resource
{
    protected static ?string $model = Recommendation::class;

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return $user?->role === Role::Coordinator && $user->hasPermission(Permission::DecidePlacements);
    }

    public static function canCreate(): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        return static::canViewAny()
            ? RecommendationReview::query(Filament::auth()->user())->with(['studentEnrollment.student.user', 'studentEnrollment.programTerm.program', 'opportunity.hostEstablishment'])
            : parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    public static function table(Table $table): Table
    {
        return $table->recordUrl(null)->columns([
            TextColumn::make('studentEnrollment.student.user.name')->label('Student')->searchable(),
            TextColumn::make('studentEnrollment.programTerm.program.code')->label('Program'),
            TextColumn::make('opportunity.title')->label('Opportunity')->searchable(),
            TextColumn::make('similarity_score')->label('Cosine similarity')->numeric(decimalPlaces: 4),
            TextColumn::make('distance_km')->label('Straight-line km')->numeric(decimalPlaces: 2)->placeholder('Unknown'),
            TextColumn::make('generated_at')->dateTime(),
        ])->recordActions([
            Action::make('review')->label('Review recommendation')->modalHeading('Review frozen recommendation')
                ->modalDescription('Facts are frozen at generation time. Labels are coordinator judgments, not final placements or proof of a trained model. Each submission preserves earlier judgment history.')
                ->fillForm(fn (Recommendation $record) => [
                    'facts' => RecommendationReview::explanation($record),
                    'history' => DB::table('placement_judgments')->where('recommendation_id', $record->id)->orderByDesc('id')->get()
                        ->map(fn ($row) => $row->created_at.' | '.$row->judgment.' | '.$row->reason)->implode("\n") ?: 'No prior judgment.',
                ])
                ->schema([
                    Textarea::make('facts')->label('Frozen explanation')->rows(10)->disabled()->dehydrated(false),
                    Textarea::make('history')->label('Judgment history')->rows(4)->disabled()->dehydrated(false),
                    Select::make('judgment')->options(['suitable' => 'Suitable', 'unsuitable' => 'Unsuitable', 'uncertain' => 'Uncertain'])->required(),
                    Textarea::make('reason')->label('Reason and supporting evidence')->minLength(10)->maxLength(5000)->required(),
                    Checkbox::make('confirm')->label('I confirm this coordinator judgment for placement review.')->accepted()->required(),
                ])
                ->action(fn (Recommendation $record, array $data) => RecommendationReview::record(Filament::auth()->user(), $record, $data))
                ->successNotificationTitle('Coordinator judgment retained'),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListRecommendations::route('/')];
    }
}
