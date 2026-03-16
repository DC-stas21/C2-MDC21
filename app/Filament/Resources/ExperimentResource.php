<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExperimentResource\Pages;
use App\Models\Experiment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExperimentResource extends Resource
{
    protected static ?string $model = Experiment::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Core';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('asset'),
            Forms\Components\TextInput::make('metric')->required(),
            Forms\Components\Select::make('status')
                ->options(['running' => 'Running', 'paused' => 'Paused', 'completed' => 'Completed'])
                ->default('running')
                ->required(),
            Forms\Components\TextInput::make('winner'),
            Forms\Components\Toggle::make('confirmed')->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('asset')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('metric'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors(['warning' => 'running', 'gray' => 'paused', 'success' => 'completed']),
                Tables\Columns\IconColumn::make('confirmed')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['running' => 'Running', 'paused' => 'Paused', 'completed' => 'Completed']),
                Tables\Filters\TernaryFilter::make('confirmed'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExperiments::route('/'),
            'create' => Pages\CreateExperiment::route('/create'),
            'edit' => Pages\EditExperiment::route('/{record}/edit'),
        ];
    }
}
