<?php

namespace App\Filament\Resources\NicheConfigResource\Pages;

use App\Filament\Resources\NicheConfigResource;
use App\Jobs\Agents\WebBuilderAgentJob;
use App\Services\AssetSetupService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateNicheConfig extends CreateRecord
{
    protected static string $resource = NicheConfigResource::class;

    protected function afterCreate(): void
    {
        // 1. Auto-setup: policies + prompts
        $setup = app(AssetSetupService::class);
        $report = $setup->setup($this->record);

        // 2. Dispatch Web Builder Agent
        WebBuilderAgentJob::dispatch($this->record->id);

        Notification::make()
            ->title('Activo configurado — web en construcción')
            ->body("Políticas: {$report['policies_created']} · Prompts: {$report['prompts_created']} · Web Builder iniciado")
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
