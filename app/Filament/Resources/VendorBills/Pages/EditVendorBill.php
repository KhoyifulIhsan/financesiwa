<?php

namespace App\Filament\Resources\VendorBills\Pages;

use App\Filament\Resources\VendorBills\Tables\VendorBillsTable;
use App\Filament\Resources\VendorBills\VendorBillResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVendorBill extends EditRecord
{
    protected static string $resource = VendorBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            VendorBillsTable::getConfirmAction(),
            VendorBillsTable::getPayBillAction(),
            DeleteAction::make(),
        ];
    }
}
