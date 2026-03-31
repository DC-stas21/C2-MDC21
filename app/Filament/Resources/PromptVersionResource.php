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

    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Prompts';

    protected static ?string $modelLabel = 'Prompt';

    protected static ?string $pluralModelLabel = 'Prompts';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Configuración del prompt')
                ->description('Define el prompt que usará cada agente de IA')
                ->schema([
                    Forms\Components\Select::make('agent_type')
                        ->label('Agente')
                        ->required()
                        ->options([
                            'orchestrator' => 'Orquestador General',
                            'web_builder' => 'Web Builder',
                            'policy_brand' => 'Policy & Brand',
                            'seo_content' => 'SEO & Contenido',
                            'distribution' => 'Distribución',
                            'qa_experimentation' => 'QA & Testing',
                            'build_release' => 'Build & Release',
                            'infra_reliability' => 'Infra & Monitoreo',
                        ]),
                    Forms\Components\TextInput::make('version')
                        ->label('Versión')
                        ->numeric()
                        ->required()
                        ->default(1),
                    Forms\Components\Select::make('model')
                        ->label('Modelo IA')
                        ->required()
                        ->options([
                            'claude-sonnet-4-5' => 'Claude Sonnet 4.5',
                            'claude-haiku-4-5' => 'Claude Haiku 4.5',
                            'gpt-4o' => 'GPT-4o',
                            'gpt-4o-mini' => 'GPT-4o Mini',
                        ]),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Activo')
                        ->default(false)
                        ->helperText('Solo un prompt activo por agente'),
                ])->columns(2),

            Forms\Components\Section::make('Prompt')
                ->schema([
                    Forms\Components\Textarea::make('prompt_text')
                        ->label('Texto del prompt')
                        ->required()
                        ->rows(12)
                        ->columnSpanFull()
                        ->placeholder('Escribe el prompt...'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('agent_type')->label('Agente')->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'orchestrator' => 'Orquestador',
                        'web_builder' => 'Web Builder',
                        'policy_brand' => 'Policy',
                        'seo_content' => 'SEO',
                        'distribution' => 'Distribución',
                        'qa_experimentation' => 'QA',
                        'build_release' => 'Build',
                        'infra_reliability' => 'Infra',
                        default => $state,
                    })->sortable(),
                Tables\Columns\TextColumn::make('version')->label('v')->sortable(),
                Tables\Columns\TextColumn::make('model')->label('Modelo')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('prompt_text')->label('Prompt')->limit(60),
                Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->since()->sortable(),
            ])
            ->defaultSort('agent_type')
            ->filters([
                Tables\Filters\SelectFilter::make('agent_type')->label('Agente')
                    ->options([
                        'orchestrator' => 'Orquestador',
                        'web_builder' => 'Web Builder',
                        'policy_brand' => 'Policy',
                        'seo_content' => 'SEO',
                        'distribution' => 'Distribución',
                        'qa_experimentation' => 'QA',
                        'build_release' => 'Build',
                        'infra_reliability' => 'Infra',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (PromptVersion $record) {
                        $record->replicate()->fill([
                            'version' => $record->version + 1,
                            'is_active' => false,
                        ])->save();
                    }),
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
