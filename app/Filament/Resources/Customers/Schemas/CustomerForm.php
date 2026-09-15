<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->label('Kode Pelanggan')
                    ->required(),
                TextInput::make('name')->label('Nama')
                    ->required(),
                Textarea::make('address')->label('Alamat')
                    ->columnSpanFull(),
                TextInput::make('phone')->label('Telepon'),
                TextInput::make('email')->label('Email')
                    ->email(),
                Toggle::make('is_active')->label('Status Aktif')
                    ->default(true),
            ]);
    }
}
