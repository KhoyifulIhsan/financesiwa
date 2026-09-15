<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_number')->label('No. Tagihan')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'INV-'.strtoupper(uniqid())),
                Select::make('customer_id')->label('Pelanggan')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->required(),
                DatePicker::make('issue_date')->label('Tanggal Terbit')
                    ->required(),
                DatePicker::make('due_date')->label('Jatuh Tempo')
                    ->required(),
                Select::make('status')->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Terkirim',
                        'partial' => 'Sebagian',
                        'paid' => 'Lunas',
                        'canceled' => 'Dibatalkan',
                    ])
                    ->default('draft')
                    ->required(),

                Repeater::make('invoiceDetails')
                    ->label('Detail Tagihan')
                    ->relationship()
                    ->schema([
                        TextInput::make('description')->label('Deskripsi')
                            ->required(),
                        TextInput::make('quantity')->label('Volume BBM / Qty')
                            ->numeric()
                            ->default(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                $set('total_price', (float) $state * (float) $get('unit_price'));
                            }),
                        TextInput::make('unit_price')->label('Harga Satuan')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                $set('total_price', (float) $state * (float) $get('quantity'));
                            }),
                        TextInput::make('total_price')->label('Total Harga')
                            ->numeric()
                            ->default(0)
                            ->readOnly(),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        self::updateTotals($set, $get);
                    }),

                TextInput::make('subtotal')->label('Subtotal')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->readOnly(),
                TextInput::make('tax')->label('Pajak')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        self::updateTotals($set, $get);
                    }),
                TextInput::make('total_amount')->label('Total Tagihan')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->readOnly(),

                Textarea::make('notes')->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }

    public static function updateTotals(Set $set, Get $get): void
    {
        $details = $get('invoiceDetails');
        $subtotal = 0;

        if (is_array($details)) {
            foreach ($details as $detail) {
                $subtotal += (float) ($detail['total_price'] ?? 0);
            }
        }

        $set('subtotal', $subtotal);
        $tax = (float) ($get('tax') ?? 0);
        $set('total_amount', $subtotal + $tax);
    }
}
