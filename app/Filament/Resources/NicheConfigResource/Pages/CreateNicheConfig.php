<?php

namespace App\Filament\Resources\NicheConfigResource\Pages;

use App\Filament\Resources\NicheConfigResource;
use App\Services\AssetSetupService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateNicheConfig extends CreateRecord
{
    protected static string $resource = NicheConfigResource::class;

    protected function afterCreate(): void
    {
        $setup = app(AssetSetupService::class);
        $report = $setup->setup($this->record);

        Notification::make()
            ->title('Activo configurado automáticamente')
            ->body("Políticas creadas: {$report['policies_created']} · Prompts creados: {$report['prompts_created']}")
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
