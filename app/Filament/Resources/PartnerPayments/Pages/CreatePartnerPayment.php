<?php

namespace App\Filament\Resources\PartnerPayments\Pages;

use App\Filament\Resources\PartnerPayments\PartnerPaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePartnerPayment extends CreateRecord
{
    protected static string $resource = PartnerPaymentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
