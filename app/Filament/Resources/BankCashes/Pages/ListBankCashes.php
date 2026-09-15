<?php

namespace App\Filament\Resources\BankCashes\Pages;

use App\Filament\Resources\BankCashes\BankCashResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBankCashes extends ListRecords
{
    protected static string $resource = BankCashResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
