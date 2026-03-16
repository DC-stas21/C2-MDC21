<?php

namespace App\Filament\Resources\EditorialCalendarResource\Pages;

use App\Filament\Resources\EditorialCalendarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEditorialCalendars extends ListRecords
{
    protected static string $resource = EditorialCalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
