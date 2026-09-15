<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_number')
                    ->label('No. Tagihan (Invoice)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'INV-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -4))),

                Select::make('customer_id')
                    ->label('Pelanggan')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                DatePicker::make('invoice_date')
                    ->label('Tanggal Invoice')
                    ->default(now())
                    ->required(),

                DatePicker::make('due_date')
                    ->label('Jatuh Tempo')
                    ->nullable(),

                TextInput::make('total_amount')
                    ->label('Total Tagihan')
                    ->numeric()
                    ->readOnly()
                    ->default(0.0)
                    ->prefix('Rp')
                    ->helperText('Dihitung otomatis dari total pengiriman (trips) yang dikaitkan.'),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'DRAFT' => 'DRAFT',
                        'UNPAID' => 'UNPAID',
                        'PAID' => 'PAID',
                    ])
                    ->default('DRAFT')
                    ->required(),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }
}
