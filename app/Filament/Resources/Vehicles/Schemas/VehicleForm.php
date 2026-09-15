<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('license_plate')->label('Plat Nomor')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('capacity')->label('Kapasitas (KL)')
                    ->numeric()
                    ->required(),
                TextInput::make('driver_name')->label('Nama Sopir'),
                Select::make('status')->label('Status')
                    ->options([
                        'aktif' => 'Aktif',
                        'maintenance' => 'Maintenance',
                    ])
                    ->default('aktif')
                    ->required(),
                Select::make('ownership_status')
                    ->label('Status Kepemilikan')
                    ->options([
                        'Milik PT' => 'Milik PT',
                        'Milik Mitra' => 'Milik Mitra',
                    ])
                    ->default('Milik PT')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($set, $state) => $state === 'Milik PT' ? $set('investor_id', null) : null),
                Select::make('investor_id')
                    ->label('Mitra / Investor')
                    ->relationship('investor', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->visible(fn ($get) => $get('ownership_status') === 'Milik Mitra')
                    ->required(fn ($get) => $get('ownership_status') === 'Milik Mitra'),
            ]);
    }
}
