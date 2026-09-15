<?php

namespace App\Filament\Resources\BankCashes;

use App\Filament\Resources\BankCashes\Pages\CreateBankCash;
use App\Filament\Resources\BankCashes\Pages\EditBankCash;
use App\Filament\Resources\BankCashes\Pages\ListBankCashes;
use App\Filament\Resources\BankCashes\Schemas\BankCashForm;
use App\Filament\Resources\BankCashes\Tables\BankCashesTable;
use App\Models\BankCash;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BankCashResource extends Resource
{
    protected static ?string $model = BankCash::class;

    protected static ?string $navigationLabel = 'Kas & Bank';

    protected static ?string $modelLabel = 'Kas & Bank';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BankCashForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankCashesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankCashes::route('/'),
            'create' => CreateBankCash::route('/create'),
            'edit' => EditBankCash::route('/{record}/edit'),
        ];
    }
}
