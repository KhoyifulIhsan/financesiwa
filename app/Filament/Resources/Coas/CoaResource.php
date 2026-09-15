<?php

namespace App\Filament\Resources\Coas;

use App\Filament\Resources\Coas\Pages\CreateCoa;
use App\Filament\Resources\Coas\Pages\EditCoa;
use App\Filament\Resources\Coas\Pages\ListCoas;
use App\Filament\Resources\Coas\Schemas\CoaForm;
use App\Filament\Resources\Coas\Tables\CoasTable;
use App\Models\Coa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CoaResource extends Resource
{
    protected static ?string $model = Coa::class;

    protected static ?string $navigationLabel = 'Bagan Akun (CoA)';

    protected static ?string $modelLabel = 'Bagan Akun';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CoaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CoasTable::configure($table);
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
            'index' => ListCoas::route('/'),
            'create' => CreateCoa::route('/create'),
            'edit' => EditCoa::route('/{record}/edit'),
        ];
    }
}
