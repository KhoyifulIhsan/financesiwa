<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->label('Kode Vendor')
                    ->required(),
                TextInput::make('name')->label('Nama')
                    ->required(),
                Textarea::make('address')->label('Alamat')
                    ->columnSpanFull(),
                TextInput::make('phone')->label('Telepon'),
                Select::make('type')->label('Tipe')
                    ->options([
                        'bbm' => 'BBM',
                        'sparepart' => 'Sparepart',
                        'maintenance' => 'Maintenance',
                        'other' => 'Lainnya',
                    ])
                    ->required(),
                Toggle::make('is_active')->label('Status Aktif')
                    ->default(true),
            ]);
    }
}
