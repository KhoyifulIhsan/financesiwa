<?php

namespace App\Filament\Resources\Coas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CoaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->label('Kode Akun')
                    ->required(),
                TextInput::make('name')->label('Nama')
                    ->required(),
                Select::make('type')->label('Tipe')
                    ->options([
                        'asset' => 'Aset',
                        'liability' => 'Kewajiban',
                        'equity' => 'Ekuitas',
                        'revenue' => 'Pendapatan',
                        'expense' => 'Beban',
                    ])
                    ->required(),
                Select::make('parent_id')->label('Akun Induk (Parent)')
                    ->relationship('parent', 'name')
                    ->searchable(),
                Toggle::make('is_active')->label('Status Aktif')
                    ->default(true),
            ]);
    }
}
