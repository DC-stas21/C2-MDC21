<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Business';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('asset'),
            Forms\Components\TextInput::make('provider'),
            Forms\Components\TextInput::make('score')->numeric()->default(0)->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'new' => 'New',
                    'qualified' => 'Qualified',
                    'contacted' => 'Contacted',
                    'converted' => 'Converted',
                    'discarded' => 'Discarded',
                ])
                ->default('new')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('asset')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('provider')->searchable(),
                Tables\Columns\TextColumn::make('score')->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'new',
                        'warning' => 'qualified',
                        'primary' => 'contacted',
                        'success' => 'converted',
                        'danger' => 'discarded',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'qualified' => 'Qualified',
                        'contacted' => 'Contacted',
                        'converted' => 'Converted',
                        'discarded' => 'Discarded',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
