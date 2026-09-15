<?php

namespace App\Filament\Resources\Tariffs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TariffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Rute / Skema Tarif')
                    ->required()
                    ->maxLength(255),
                TextInput::make('customer_price')
                    ->label('Harga ke Pelanggan (Per Satuan)')
                    ->numeric()
                    ->required()
                    ->prefix('Rp'),
                Select::make('fee_type')
                    ->label('Tipe Skema Margin')
                    ->options([
                        'fixed' => 'Potongan Tetap (Rupiah)',
                        'percentage' => 'Bagi Hasil (Persentase)',
                    ])
                    ->required()
                    ->live(),
                TextInput::make('pt_margin')
                    ->label('Margin PT Sinar Wardana')
                    ->numeric()
                    ->required()
                    ->helperText(fn ($get) => $get('fee_type') === 'percentage'
                        ? 'Masukkan angka persentase (Contoh: 20 untuk 20%). Hak Mitra otomatis sisa persennya.'
                        : 'Masukkan nominal Rupiah (Contoh: 10). Hak Mitra otomatis Harga Pelanggan - Margin PT.'),
            ]);
    }
}
