<?php

namespace App\Filament\Resources\BankCashes\Pages;

use App\Filament\Resources\BankCashes\BankCashResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBankCash extends EditRecord
{
    protected static string $resource = BankCashResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
