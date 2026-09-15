<?php

namespace App\Filament\Resources\PartnerPayments;

use App\Filament\Resources\PartnerPayments\Pages\CreatePartnerPayment;
use App\Filament\Resources\PartnerPayments\Pages\EditPartnerPayment;
use App\Filament\Resources\PartnerPayments\Pages\ListPartnerPayments;
use App\Filament\Resources\PartnerPayments\Schemas\PartnerPaymentForm;
use App\Filament\Resources\PartnerPayments\Tables\PartnerPaymentsTable;
use App\Models\PartnerPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PartnerPaymentResource extends Resource
{
    protected static ?string $model = PartnerPayment::class;

    protected static ?string $navigationLabel = 'Pencairan Mitra';

    protected static ?string $modelLabel = 'Pencairan Mitra';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'reference_number';

    public static function form(Schema $schema): Schema
    {
        return PartnerPaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartnerPaymentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartnerPayments::route('/'),
            'create' => CreatePartnerPayment::route('/create'),
            'edit' => EditPartnerPayment::route('/{record}/edit'),
        ];
    }
}
