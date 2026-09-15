<?php

namespace App\Filament\Resources\Tariffs;

use App\Filament\Resources\Tariffs\Pages\CreateTariff;
use App\Filament\Resources\Tariffs\Pages\EditTariff;
use App\Filament\Resources\Tariffs\Pages\ListTariffs;
use App\Filament\Resources\Tariffs\Schemas\TariffForm;
use App\Filament\Resources\Tariffs\Tables\TariffsTable;
use App\Models\Tariff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TariffResource extends Resource
{
    protected static ?string $model = Tariff::class;

    protected static ?string $navigationLabel = 'Skema Tarif (Tariff)';

    protected static ?string $modelLabel = 'Skema Tarif';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TariffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TariffsTable::configure($table);
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
            'index' => ListTariffs::route('/'),
            'create' => CreateTariff::route('/create'),
            'edit' => EditTariff::route('/{record}/edit'),
        ];
    }
}
