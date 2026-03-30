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

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Activos';

    protected static ?string $modelLabel = 'Activo';

    protected static ?string $pluralModelLabel = 'Activos';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del activo')
                ->description('Datos principales del dominio')
                ->schema([
                    Forms\Components\TextInput::make('domain')
                        ->label('Dominio')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->placeholder('ejemplo.es'),
                    Forms\Components\Select::make('vertical')
                        ->label('Vertical')
                        ->required()
                        ->options([
                            'Hipotecas' => 'Hipotecas',
                            'Energía' => 'Energía',
                            'Seguros' => 'Seguros',
                            'Préstamos' => 'Préstamos',
                            'Solar' => 'Solar',
                        ])
                        ->searchable(),
                    Forms\Components\TextInput::make('cpl')
                        ->label('CPL (€)')
                        ->numeric()
                        ->prefix('€'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true)
                        ->helperText('Los agentes solo operan en activos activos'),
                ])->columns(2),

            Forms\Components\Section::make('Configuración')
                ->schema([
                    Forms\Components\KeyValue::make('config')
                        ->label('Parámetros')
                        ->addActionLabel('Añadir')
                        ->keyLabel('Clave')
                        ->valueLabel('Valor'),
                    Forms\Components\KeyValue::make('colors')
                        ->label('Colores')
                        ->addActionLabel('Añadir')
                        ->keyLabel('Nombre')
                        ->valueLabel('Hex'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('domain')->label('Dominio')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('vertical')->label('Vertical')->badge()->sortable(),
                Tables\Columns\TextColumn::make('cpl')->label('CPL')->money('eur')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->since()->sortable(),
            ])
            ->defaultSort('domain')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Estado'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
