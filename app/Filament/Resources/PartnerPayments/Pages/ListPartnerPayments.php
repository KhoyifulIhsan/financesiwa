<?php

namespace App\Filament\Resources\PartnerPayments\Pages;

use App\Filament\Resources\PartnerPayments\PartnerPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPartnerPayments extends ListRecords
{
    protected static string $resource = PartnerPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
