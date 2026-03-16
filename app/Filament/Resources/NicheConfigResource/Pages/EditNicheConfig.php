<?php

namespace App\Filament\Resources\NicheConfigResource\Pages;

use App\Filament\Resources\NicheConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNicheConfig extends EditRecord
{
    protected static string $resource = NicheConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
