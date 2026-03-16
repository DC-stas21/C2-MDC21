<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NicheConfigResource\Pages;
use App\Models\NicheConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NicheConfigResource extends Resource
{
    protected static ?string $model = NicheConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Assets';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('domain')->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('vertical')->required(),
            Forms\Components\TextInput::make('cpl')->numeric()->prefix('$'),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('domain')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('vertical')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('cpl')->money('usd')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('domain', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\SelectFilter::make('vertical')
                    ->options(fn () => NicheConfig::distinct()->pluck('vertical', 'vertical')->toArray()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNicheConfigs::route('/'),
            'create' => Pages\CreateNicheConfig::route('/create'),
            'edit' => Pages\EditNicheConfig::route('/{record}/edit'),
        ];
    }
}
