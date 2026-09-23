<?php

namespace App\Filament\Resources\Students;

use App\Enums\EnrollmentStatus;
use App\Enums\Permission;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Models\AcademicTerm;
use App\Models\Program;
use App\Models\StudentEnrollment;
use App\Services\StudentAccess;
use App\Services\StudentEnrollmentManagement;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class StudentResource extends Resource
{
    protected static ?string $model = StudentEnrollment::class;

    protected static ?string $slug = 'students';

    protected static ?string $modelLabel = 'student enrollment';

    protected static ?string $navigationLabel = 'Students';

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->canAccessPanel(Filament::getCurrentOrDefaultPanel()) ?? false;
    }

    public static function canCreate(): bool
    {
        return Filament::auth()->user()?->hasPermission(Permission::ManageAcademicRecords) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (! static::canViewAny()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('id', StudentAccess::enrollments(Filament::auth()->user())->select('id'))
            ->with(['student.user', 'programTerm.program', 'programTerm.academicTerm']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('student_email')->email()->required()->visibleOn('create')
                ->helperText('Use an existing student account. Account creation is managed by the administrator.'),
            TextInput::make('student_number')->required()->maxLength(50)->visibleOn('create'),
            Select::make('program_id')->label('Program')->required()->visibleOn('create')
                ->options(fn () => Program::where('is_active', true)->orderBy('code')->pluck('name', 'id')),
            Select::make('academic_term_id')->label('Academic term')->required()->visibleOn('create')
                ->options(fn () => AcademicTerm::where('is_active', true)->orderByDesc('starts_on')->pluck('name', 'id')),
            TextInput::make('year_level')->integer()->required()->minValue(1)->maxValue(10),
            DatePicker::make('enrolled_on')->required(),
            DatePicker::make('target_completion_on')->afterOrEqual('enrolled_on'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('student.student_number')->label('Student number')->searchable(),
            TextColumn::make('student.user.name')->label('Student')->searchable(),
            TextColumn::make('programTerm.program.code')->label('Program')->searchable(),
            TextColumn::make('programTerm.academicTerm.name')->label('Term'),
            TextColumn::make('year_level')->label('Year'),
            TextColumn::make('status')->badge(),
            TextColumn::make('enrolled_on')->date()->sortable(),
            TextColumn::make('target_completion_on')->date()->placeholder('Not set'),
        ])->filters([
            SelectFilter::make('status')->options(['enrolled' => 'Enrolled', 'withdrawn' => 'Withdrawn', 'completed' => 'Completed']),
        ])->recordActions([
            EditAction::make()->visible(fn (StudentEnrollment $record) => $record->status === EnrollmentStatus::Enrolled),
            Action::make('withdraw')->label('Withdraw enrollment')->color('danger')
                ->authorize(fn (StudentEnrollment $record) => Gate::allows('update', $record))
                ->visible(fn (StudentEnrollment $record) => $record->status === EnrollmentStatus::Enrolled && ! $record->placements()->exists())
                ->requiresConfirmation()->modalDescription('Withdraw this enrollment while retaining its records and audit history.')
                ->schema([Textarea::make('reason')->required()->maxLength(2000)])
                ->action(fn (StudentEnrollment $record, array $data) => StudentEnrollmentManagement::withdraw(Filament::auth()->user(), $record, $data))
                ->successNotificationTitle('Enrollment withdrawn'),
        ])
            ->defaultSort('enrolled_on', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListStudents::route('/'), 'create' => CreateStudent::route('/create'), 'edit' => EditStudent::route('/{record}/edit')];
    }
}
