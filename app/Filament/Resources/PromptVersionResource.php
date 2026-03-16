<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromptVersionResource\Pages;
use App\Models\PromptVersion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromptVersionResource extends Resource
{
    protected static ?string $model = PromptVersion::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Core';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('agent_type')->required(),
            Forms\Components\TextInput::make('version')->numeric()->required(),
            Forms\Components\TextInput::make('model')->required(),
            Forms\Components\Textarea::make('prompt_text')->required()->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('agent_type')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('version')->sortable(),
                Tables\Columns\TextColumn::make('model')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('agent_type')
                    ->options(fn () => PromptVersion::distinct()->pluck('agent_type', 'agent_type')->toArray()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromptVersions::route('/'),
            'create' => Pages\CreatePromptVersion::route('/create'),
            'edit' => Pages\EditPromptVersion::route('/{record}/edit'),
        ];
    }
}
