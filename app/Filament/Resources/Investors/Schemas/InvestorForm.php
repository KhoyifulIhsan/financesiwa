<?php

namespace App\Filament\Resources\Investors\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InvestorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Mitra / Investor')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Nomor Telepon / HP')
                    ->tel()
                    ->maxLength(50),
                Textarea::make('bank_account_info')
                    ->label('Informasi Rekening Bank')
                    ->placeholder('Contoh: Bank BCA 1234567890 a/n John Doe')
                    ->columnSpanFull(),
            ]);
    }
}
