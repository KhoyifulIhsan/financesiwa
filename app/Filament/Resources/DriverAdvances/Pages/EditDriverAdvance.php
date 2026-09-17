<?php

namespace App\Filament\Resources\DriverAdvances\Pages;

use App\Filament\Resources\DriverAdvances\DriverAdvanceResource;
use App\Filament\Resources\DriverAdvances\Tables\DriverAdvancesTable;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDriverAdvance extends EditRecord
{
    protected static string $resource = DriverAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DriverAdvancesTable::getDisburseAction(),
            DeleteAction::make(),
        ];
    }
}
