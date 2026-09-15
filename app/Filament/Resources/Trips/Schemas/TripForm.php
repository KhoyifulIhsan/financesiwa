<?php

namespace App\Filament\Resources\Trips\Schemas;

use App\Models\Coa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TripForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('trip_number')
                    ->label('No. Pengiriman (Trip)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'TRIP-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -4)))
                    ->readOnly(),

                DatePicker::make('date')
                    ->label('Tanggal Pengiriman')
                    ->default(now())
                    ->required(),

                Select::make('customer_id')
                    ->label('Pelanggan')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('vehicle_id')
                    ->label('Armada / Truk')
                    ->relationship('vehicle', 'license_plate')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('tariff_id')
                    ->label('Skema Tarif')
                    ->relationship('tariff', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('driver_id')
                    ->label('Sopir (Driver)')
                    ->relationship('driver', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('route_origin')
                    ->label('Rute Asal')
                    ->placeholder('Contoh: Depo Plumpang')
                    ->required(),

                TextInput::make('route_destination')
                    ->label('Rute Tujuan')
                    ->placeholder('Contoh: SPBU 34-12345 Bekasi')
                    ->required(),

                TextInput::make('volume')
                    ->label('Volume Muatan')
                    ->numeric()
                    ->suffix('KL')
                    ->required(),

                Select::make('status')
                    ->label('Status Trip')
                    ->options([
                        'Pending' => 'Pending',
                        'In Transit' => 'Dalam Perjalanan',
                        'Completed' => 'Selesai',
                    ])
                    ->default('Pending')
                    ->required(),

                Repeater::make('tripExpenses')
                    ->label('Rincian Biaya Operasional Trip')
                    ->relationship()
                    ->schema([
                        Select::make('coa_id')
                            ->label('Akun Beban')
                            ->options(Coa::where('type', 'expense')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('description')
                            ->label('Keterangan')
                            ->placeholder('Contoh: Solar 50 Liter, Tol Cikampek, dll.')
                            ->required(),
                        TextInput::make('amount')
                            ->label('Nominal')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
