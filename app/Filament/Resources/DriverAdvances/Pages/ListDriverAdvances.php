<?php

namespace App\Filament\Resources\DriverAdvances\Pages;

use App\Filament\Resources\DriverAdvances\DriverAdvanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDriverAdvances extends ListRecords
{
    protected static string $resource = DriverAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
