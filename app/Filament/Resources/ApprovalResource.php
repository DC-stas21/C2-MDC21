<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApprovalResource\Pages;
use App\Models\Approval;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApprovalResource extends Resource
{
    protected static ?string $model = Approval::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'Core';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('action')->required(),
            Forms\Components\Select::make('level')
                ->options(['N1' => 'N1 - Auto', 'N2' => 'N2 - Semi', 'N3' => 'N3 - Humano'])
                ->default('N3')
                ->required(),
            Forms\Components\Select::make('status')
                ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'])
                ->default('pending')
                ->required(),
            Forms\Components\Textarea::make('reason')->columnSpanFull(),
            Forms\Components\Textarea::make('decision_note')->columnSpanFull(),
            Forms\Components\DateTimePicker::make('decided_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('action')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('level')
                    ->colors(['success' => 'N1', 'warning' => 'N2', 'danger' => 'N3']),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors(['warning' => 'pending', 'success' => 'approved', 'danger' => 'rejected'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('decider.name')->label('Decided By'),
                Tables\Columns\TextColumn::make('decided_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']),
                Tables\Filters\SelectFilter::make('level')
                    ->options(['N1' => 'N1', 'N2' => 'N2', 'N3' => 'N3']),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApprovals::route('/'),
            'create' => Pages\CreateApproval::route('/create'),
            'edit' => Pages\EditApproval::route('/{record}/edit'),
        ];
    }
}
