<?php

namespace App\Filament\Resources\PartnerPayments\Pages;

use App\Filament\Resources\PartnerPayments\PartnerPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPartnerPayment extends EditRecord
{
    protected static string $resource = PartnerPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
