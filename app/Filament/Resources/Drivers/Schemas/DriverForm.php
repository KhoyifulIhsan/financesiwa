<?php

namespace App\Filament\Resources\Drivers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DriverForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Sopir')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Nomor Telepon/HP')
                    ->tel()
                    ->required()
                    ->maxLength(50),
                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }
}
