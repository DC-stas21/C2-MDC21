<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EditorialCalendarResource\Pages;
use App\Models\EditorialCalendar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EditorialCalendarResource extends Resource
{
    protected static ?string $model = EditorialCalendar::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\TextInput::make('channel')->required(),
            Forms\Components\TextInput::make('asset'),
            Forms\Components\Select::make('status')
                ->options([
                    'planned' => 'Planned',
                    'drafting' => 'Drafting',
                    'ready' => 'Ready',
                    'published' => 'Published',
                ])
                ->default('planned')
                ->required(),
            Forms\Components\Select::make('assigned_to')
                ->relationship('assignee', 'name'),
            Forms\Components\DatePicker::make('scheduled_for'),
            Forms\Components\DateTimePicker::make('published_at'),
            Forms\Components\Textarea::make('draft')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->limit(50),
                Tables\Columns\TextColumn::make('channel')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('asset')->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'planned',
                        'warning' => 'drafting',
                        'primary' => 'ready',
                        'success' => 'published',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignee.name')->label('Assigned To'),
                Tables\Columns\TextColumn::make('scheduled_for')->date()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_for', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'planned' => 'Planned',
                        'drafting' => 'Drafting',
                        'ready' => 'Ready',
                        'published' => 'Published',
                    ]),
                Tables\Filters\SelectFilter::make('channel')
                    ->options(fn () => EditorialCalendar::distinct()->pluck('channel', 'channel')->toArray()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEditorialCalendars::route('/'),
            'create' => Pages\CreateEditorialCalendar::route('/create'),
            'edit' => Pages\EditEditorialCalendar::route('/{record}/edit'),
        ];
    }
}
