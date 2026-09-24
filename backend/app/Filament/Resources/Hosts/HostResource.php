<?php

namespace App\Filament\Resources\Hosts;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\HostEstablishment;
use App\Models\Program;
use App\Services\HostAccess;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HostResource extends Resource
{
    protected static ?string $model = HostEstablishment::class;

    protected static ?string $slug = 'hosts';

    protected static ?string $navigationLabel = 'Hosts';

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
        return static::canViewAny() ? HostAccess::query(Filament::auth()->user()) : parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->maxLength(50),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('address')->required()->maxLength(255),
            TextInput::make('city')->required()->maxLength(255),
            TextInput::make('industry')->maxLength(255),
            Textarea::make('description')->maxLength(10000),
            TextInput::make('contact_name')->maxLength(255),
            TextInput::make('contact_email')->email()->maxLength(255),
            TextInput::make('contact_number')->maxLength(40),
            TextInput::make('latitude')->numeric()->minValue(-90)->maxValue(90),
            TextInput::make('longitude')->numeric()->minValue(-180)->maxValue(180),
            Toggle::make('is_active')->default(true)->helperText('Inactive hosts are excluded from eligible opportunities. History is retained.'),
            Repeater::make('capacities')->label('Capacity by program and academic term')->minItems(1)->maxItems(100)->required()->reorderable(false)->deletable(false)
                ->helperText('Set capacity to zero to close slots. Existing placements and other programs are preserved.')
                ->schema([
                    Hidden::make('_existing')->default(false),
                    Select::make('program_id')->label('Program')->required()->disabled(fn (Get $get) => (bool) $get('_existing'))->dehydrated()->options(function () {
                        $user = Filament::auth()->user();
                        $query = Program::query();
                        if ($user->role === Role::Coordinator) {
                            $query->whereIn('id', $user->programs()->select('programs.id'));
                        }

                        return $query->orderBy('code')->pluck('name', 'id');
                    }),
                    Select::make('academic_term_id')->label('Academic term')->required()->disabled(fn (Get $get) => (bool) $get('_existing'))->dehydrated()->options(fn () => AcademicTerm::orderByDesc('starts_on')->pluck('name', 'id')),
                    TextInput::make('capacity')->integer()->required()->minValue(0)->maxValue(100000),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->searchable(), TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('city')->searchable(), TextColumn::make('contact_name'),
            IconColumn::make('is_active')->boolean(),
        ])->recordActions([EditAction::make()])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListHosts::route('/'), 'create' => Pages\CreateHost::route('/create'), 'edit' => Pages\EditHost::route('/{record}/edit')];
    }
}
