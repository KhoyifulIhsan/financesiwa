<?php

namespace App\Filament\Resources\BankCashes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BankCashForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('coa_id')->label('Akun (CoA)')
                    ->relationship('coa', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('name')->label('Nama')
                    ->required(),
                Select::make('type')->label('Tipe')
                    ->options([
                        'cash' => 'Kas',
                        'bank' => 'Bank',
                    ])
                    ->required(),
                TextInput::make('account_number')->label('Nomor Rekening'),
                TextInput::make('current_balance')->label('Saldo Saat Ini')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
