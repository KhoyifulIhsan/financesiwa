<?php

namespace App\Filament\Resources\DriverAdvances;

use App\Filament\Resources\DriverAdvances\Pages\CreateDriverAdvance;
use App\Filament\Resources\DriverAdvances\Pages\EditDriverAdvance;
use App\Filament\Resources\DriverAdvances\Pages\ListDriverAdvances;
use App\Filament\Resources\DriverAdvances\Schemas\DriverAdvanceForm;
use App\Filament\Resources\DriverAdvances\Tables\DriverAdvancesTable;
use App\Models\DriverAdvance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DriverAdvanceResource extends Resource
{
    protected static ?string $model = DriverAdvance::class;

    protected static ?string $navigationLabel = 'Uang Jalan Sopir';

    protected static ?string $modelLabel = 'Uang Jalan Sopir';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function form(Schema $schema): Schema
    {
        return DriverAdvanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DriverAdvancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDriverAdvances::route('/'),
            'create' => CreateDriverAdvance::route('/create'),
            'edit' => EditDriverAdvance::route('/{record}/edit'),
        ];
    }
}
