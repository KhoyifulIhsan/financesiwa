<?php

namespace App\Filament\Resources\PartnerPayments\Schemas;

use App\Models\Coa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PartnerPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('investor_id')
                    ->label('Mitra / Investor')
                    ->relationship('investor', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('bank_account_id')
                    ->label('Sumber Kas / Bank')
                    ->options(function () {
                        return Coa::query()
                            ->where('type', 'asset')
                            ->where(function ($query) {
                                $query->where('code', 'like', '11%')
                                    ->orWhere('code', 'like', '12%')
                                    ->orWhere('name', 'like', '%Kas%')
                                    ->orWhere('name', 'like', '%Bank%');
                            })
                            ->where('code', '!=', config('accounting.default_ar', '1300'))
                            ->get()
                            ->mapWithKeys(fn (Coa $coa) => [$coa->id => "{$coa->code} - {$coa->name}"]);
                    })
                    ->searchable()
                    ->preload()
                    ->required(),

                DatePicker::make('payment_date')
                    ->label('Tanggal Pembayaran')
                    ->default(now())
                    ->required(),

                TextInput::make('amount')
                    ->label('Nominal Pencairan')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                TextInput::make('reference_number')
                    ->label('No. Referensi / Bukti Transfer')
                    ->placeholder('Contoh: TRF-BCA-889123'),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull()
                    ->placeholder('Keterangan pencairan bagi hasil mitra armada truk'),
            ]);
    }
}
