<?php

namespace App\Filament\Widgets;

use App\Models\NicheConfig;
use App\Services\ScoreComposite;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class NicheScoresWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Score del portafolio';

    public function table(Table $table): Table
    {
        return $table
            ->query(NicheConfig::query()->where('is_active', true)->latest())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Activo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('domain')
                    ->label('Dominio')
                    ->url(fn ($record) => "https://{$record->domain}", shouldOpenInNewTab: true)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('vertical')
                    ->label('Vertical')
                    ->badge(),

                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->getStateUsing(function (NicheConfig $record): string {
                        $score = app(ScoreComposite::class)->getLatest($record->id);

                        return $score !== null ? number_format($score, 1) : '—';
                    })
                    ->badge()
                    ->color(function (NicheConfig $record): string {
                        $score = app(ScoreComposite::class)->getLatest($record->id);
                        if ($score === null) {
                            return 'gray';
                        }

                        return match (true) {
                            $score >= 80 => 'success',
                            $score >= 60 => 'primary',
                            $score >= 40 => 'warning',
                            default => 'danger',
                        };
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última actualización')
                    ->since()
                    ->sortable(),
            ])
            ->emptyStateHeading('Sin activos configurados')
            ->emptyStateDescription('Crea el primer activo en Niche Configs.')
            ->emptyStateIcon('heroicon-o-globe-alt')
            ->paginated(10);
    }
}
