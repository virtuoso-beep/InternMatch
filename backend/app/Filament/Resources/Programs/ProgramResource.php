<?php

namespace App\Filament\Resources\Programs;

use App\Enums\Role;
use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\Programs\Pages\ListPrograms;
use App\Models\Program;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return $user?->canAccessPanel(Filament::getCurrentOrDefaultPanel()) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        $query = parent::getEloquentQuery();
        if (! static::canViewAny()) {
            return $query->whereRaw('1 = 0');
        }

        return $user->role === Role::Admin ? $query : $query->whereIn('id', $user->programs()->select('programs.id'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->disabled()->dehydrated(false),
            TextInput::make('name')->disabled()->dehydrated(false),
            TextInput::make('required_ojt_hours')->label('Confirmed required OJT hours')->integer()->minValue(1)->maxValue(10000)
                ->helperText('Leave empty until the department confirms the required hours.'),
            TextInput::make('internship_term')->maxLength(255),
            Toggle::make('is_active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->searchable()->sortable(),
            TextColumn::make('name')->searchable(),
            TextColumn::make('required_ojt_hours')->placeholder('Unconfirmed'),
            TextColumn::make('internship_term')->placeholder('Unconfirmed'),
        ])->recordActions([EditAction::make()])->defaultSort('code');
    }

    public static function getPages(): array
    {
        return ['index' => ListPrograms::route('/'), 'edit' => EditProgram::route('/{record}/edit')];
    }
}
