<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NicheConfigResource\Pages;
use App\Jobs\Agents\WebBuilderAgentJob;
use App\Models\NicheConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
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
            Forms\Components\Section::make('¿Qué activo quieres crear?')
                ->description('Con estos datos C2 auto-configura políticas y prompts para los agentes')
                ->schema([
                    Forms\Components\TextInput::make('domain')
                        ->label('Dominio')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->placeholder('calculahipoteca.es')
                        ->helperText('Solo el dominio, sin https://'),
                    Forms\Components\Select::make('vertical')
                        ->label('Vertical')
                        ->required()
                        ->options([
                            'Hipotecas' => 'Hipotecas',
                            'Energía' => 'Energía',
                            'Seguros' => 'Seguros',
                            'Préstamos' => 'Préstamos',
                            'Solar' => 'Solar',
                            'Inmobiliaria' => 'Inmobiliaria',
                            'Inversión' => 'Inversión',
                            'Telecomunicaciones' => 'Telecomunicaciones',
                        ])
                        ->searchable()
                        ->helperText('Sector del activo. Determina las fuentes y reglas de contenido.'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Activar ahora')
                        ->default(true)
                        ->helperText('Si lo activas, los agentes empiezan a operar sobre este activo'),
                ])->columns(2),

            Forms\Components\Section::make('Contexto para los agentes')
                ->description('Cuanta más información des, mejor trabajarán los agentes de IA')
                ->schema([
                    Forms\Components\Textarea::make('config.description')
                        ->label('Descripción del activo')
                        ->required()
                        ->rows(3)
                        ->placeholder('Calculadora online de hipotecas para el mercado español. Permite simular cuotas mensuales, comparar tipos fijos vs variables, y estimar gastos de formalización.')
                        ->helperText('¿Qué es este activo? ¿Qué hace? ¿Qué problema resuelve?')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('config.target_audience')
                        ->label('Audiencia')
                        ->required()
                        ->placeholder('Personas de 25-45 años que quieren comprar su primera vivienda en España')
                        ->helperText('¿A quién va dirigido?')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('config.tone')
                        ->label('Tono')
                        ->required()
                        ->options([
                            'profesional y cercano' => 'Profesional y cercano',
                            'técnico pero accesible' => 'Técnico pero accesible',
                            'informal y directo' => 'Informal y directo',
                            'formal y autoritativo' => 'Formal y autoritativo',
                            'educativo y empático' => 'Educativo y empático',
                        ])
                        ->default('profesional y cercano')
                        ->helperText('Cómo deben hablar los agentes al crear contenido'),
                    Forms\Components\TextInput::make('config.keywords')
                        ->label('Keywords principales')
                        ->placeholder('hipotecas España, calculadora hipoteca, simulador cuota mensual')
                        ->helperText('Separadas por coma. Los agentes SEO las usarán como foco.')
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('config.notes')
                        ->label('Notas adicionales')
                        ->rows(2)
                        ->placeholder('No mencionar bancos específicos. Enfoque en educación financiera, no en venta directa.')
                        ->helperText('Cualquier instrucción extra para los agentes')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Diseño')
                ->description('Colores del activo')
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('colors.primary')
                        ->label('Color primario')
                        ->placeholder('#4f46e5')
                        ->helperText('Hex del color principal'),
                    Forms\Components\TextInput::make('colors.secondary')
                        ->label('Color secundario')
                        ->placeholder('#0ea5e9'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('domain')
                    ->label('Dominio')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('vertical')
                    ->label('Vertical')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('config.description')
                    ->label('Descripción')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->config['description'] ?? ''),
                Tables\Columns\TextColumn::make('build_status')
                    ->label('Estado web')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'gray',
                        'building' => 'warning',
                        'staging' => 'info',
                        'live' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => 'Pendiente',
                        'building' => 'Construyendo...',
                        'staging' => 'En staging',
                        'live' => 'Publicado',
                        'failed' => 'Error',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('rebuild')
                    ->label('Reconstruir')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('¿Reconstruir web?')
                    ->modalDescription('Esto regenerará el diseño y contenido de la web. El build actual se sobrescribirá.')
                    ->action(function (NicheConfig $record) {
                        WebBuilderAgentJob::dispatch($record->id);
                        Notification::make()->title('Web Builder iniciado')->body("Reconstruyendo {$record->domain}")->success()->send();
                    }),
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
