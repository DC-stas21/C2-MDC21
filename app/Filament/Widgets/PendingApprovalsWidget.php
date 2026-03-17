<?php

namespace App\Filament\Widgets;

use App\Models\Approval;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingApprovalsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Aprobaciones pendientes (N3)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Approval::query()
                    ->where('status', 'pending')
                    ->with('agentRun')
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('agentRun.agent_type')
                    ->label('Agente')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('action')
                    ->label('Acción')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->action),

                Tables\Columns\TextColumn::make('level')
                    ->label('Nivel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'N1' => 'gray',
                        'N2' => 'warning',
                        'N3' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Razón')
                    ->limit(80)
                    ->tooltip(fn ($record) => $record->reason),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Solicitado')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Approval $record) => $record->update([
                        'status' => 'approved',
                        'decided_by' => auth()->id(),
                        'decided_at' => now(),
                    ])),

                Tables\Actions\Action::make('deny')
                    ->label('Denegar')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Approval $record) => $record->update([
                        'status' => 'denied',
                        'decided_by' => auth()->id(),
                        'decided_at' => now(),
                    ])),
            ])
            ->emptyStateHeading('Sin aprobaciones pendientes')
            ->emptyStateDescription('Todos los agentes están operando dentro de N1/N2.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->paginated(false);
    }
}
