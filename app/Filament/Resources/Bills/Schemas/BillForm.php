<?php

namespace App\Filament\Resources\Bills\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Schema;

class BillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('bill_number')
                    ->label('No. Tagihan Vendor')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'BILL-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -4)))
                    ->readOnly(),

                Select::make('vendor_id')
                    ->label('Vendor / Supplier')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                DatePicker::make('bill_date')
                    ->label('Tanggal Tagihan')
                    ->default(now())
                    ->required(),

                DatePicker::make('due_date')
                    ->label('Jatuh Tempo')
                    ->default(now()->addDays(30))
                    ->required(),

                Select::make('status')
                    ->label('Status Pembayaran')
                    ->options([
                        'Unpaid' => 'Belum Dibayar (Unpaid)',
                        'Partial' => 'Sebagian (Partial)',
                        'Paid' => 'Lunas (Paid)',
                    ])
                    ->default('Unpaid')
                    ->required(),

                Textarea::make('notes')
                    ->label('Catatan Tagihan')
                    ->rows(2)
                    ->nullable()
                    ->columnSpanFull(),

                Repeater::make('billDetails')
                    ->label('Rincian Tagihan Vendor')
                    ->relationship('billDetails')
                    ->schema([
                        TextInput::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Contoh: Tagihan Ban Truck Hino, Solar Dexlite, Servis Rutin')
                            ->required(),
                        TextInput::make('amount')
                            ->label('Nominal')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                self::updateTotals($set, $get);
                            }),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        self::updateTotals($set, $get);
                    }),

                TextInput::make('total_amount')
                    ->label('Total Tagihan')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->readOnly(),
            ]);
    }

    public static function updateTotals(Set $set, Get $get): void
    {
        $details = $get('billDetails');
        $total = 0;

        if (is_array($details)) {
            foreach ($details as $detail) {
                $total += (float) ($detail['amount'] ?? 0);
            }
        }

        $set('total_amount', $total);
    }
}
