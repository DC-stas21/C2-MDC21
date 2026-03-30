<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PolicyResource\Pages;
use App\Models\NicheConfig;
use App\Models\Policy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PolicyResource extends Resource
{
    protected static ?string $model = Policy::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Políticas';

    protected static ?string $modelLabel = 'Política';

    protected static ?string $pluralModelLabel = 'Políticas';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Política de contenido')
                ->description('El agente PolicyBrand evalúa estas reglas antes de aprobar contenido')
                ->schema([
                    Forms\Components\Select::make('scope')
                        ->label('Alcance')
                        ->required()
                        ->options(function () {
                            $options = ['global' => 'Global (todos los activos)'];
                            foreach (NicheConfig::pluck('domain') as $domain) {
                                $options[$domain] = $domain;
                            }

                            return $options;
                        })
                        ->searchable(),
                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->required()
                        ->options([
                            'tone' => 'Tono y estilo',
                            'legal' => 'Legal y compliance',
                            'brand' => 'Marca y competencia',
                            'privacy' => 'Privacidad (RGPD)',
                            'seo' => 'SEO y estructura',
                            'content' => 'Contenido específico',
                        ]),
                    Forms\Components\Textarea::make('content')
                        ->label('Regla')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull()
                        ->placeholder('Describe la regla...'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Activa')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('scope')->label('Alcance')->badge()
                    ->color(fn (string $state) => $state === 'global' ? 'primary' : 'gray')->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Tipo')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'legal', 'privacy' => 'danger',
                        'tone', 'brand' => 'warning',
                        default => 'info',
                    })->sortable(),
                Tables\Columns\TextColumn::make('content')->label('Regla')->limit(80)
                    ->tooltip(fn ($record) => $record->content),
                Tables\Columns\IconColumn::make('is_active')->label('Activa')->boolean(),
            ])
            ->defaultSort('scope')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\SelectFilter::make('type')->label('Tipo')
                    ->options(['tone' => 'Tono', 'legal' => 'Legal', 'brand' => 'Marca', 'privacy' => 'Privacidad', 'seo' => 'SEO', 'content' => 'Contenido']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPolicies::route('/'),
            'create' => Pages\CreatePolicy::route('/create'),
            'edit' => Pages\EditPolicy::route('/{record}/edit'),
        ];
    }
}
