<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArtifactResource\Pages;
use App\Models\Artifact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ArtifactResource extends Resource
{
    protected static ?string $model = Artifact::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Core';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('agent_run_id')
                ->relationship('agentRun')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->agent_type.' #'.$record->id),
            Forms\Components\TextInput::make('type')->required(),
            Forms\Components\TextInput::make('path'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('path')->searchable(),
                Tables\Columns\TextColumn::make('agentRun.agent_type')->label('Agent'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(fn () => Artifact::distinct()->pluck('type', 'type')->toArray()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArtifacts::route('/'),
            'create' => Pages\CreateArtifact::route('/create'),
            'edit' => Pages\EditArtifact::route('/{record}/edit'),
        ];
    }
}
