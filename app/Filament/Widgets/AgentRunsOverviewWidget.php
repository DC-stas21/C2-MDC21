<?php

namespace App\Filament\Widgets;

use App\Models\AgentRun;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AgentRunsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $running = AgentRun::where('status', 'running')->count();
        $todayCompleted = AgentRun::where('status', 'completed')
            ->whereDate('finished_at', today())
            ->count();
        $todayFailed = AgentRun::where('status', 'failed')
            ->whereDate('finished_at', today())
            ->count();
        $pending = AgentRun::where('status', 'pending')->count();

        return [
            Stat::make('Agentes ejecutando', $running)
                ->description('En este momento')
                ->color($running > 0 ? 'primary' : 'gray')
                ->icon('heroicon-o-cpu-chip'),

            Stat::make('Completados hoy', $todayCompleted)
                ->description('Ejecuciones exitosas')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Fallidos hoy', $todayFailed)
                ->description('Requieren revisión')
                ->color($todayFailed > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-x-circle'),

            Stat::make('En cola', $pending)
                ->description('Pendientes de ejecución')
                ->color('warning')
                ->icon('heroicon-o-clock'),
        ];
    }
}
