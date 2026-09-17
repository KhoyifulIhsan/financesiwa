<?php

namespace App\Filament\Resources\VendorBills\Schemas;

use App\Models\Coa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VendorBillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('vendor_id')
                    ->label('Vendor / Supplier')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('bill_number')
                    ->label('No. Tagihan Vendor')
                    ->default(fn () => 'V-BILL-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -4)))
                    ->unique(ignoreRecord: true)
                    ->required(),

                Select::make('expense_account_id')
                    ->label('Kategori Akun Biaya')
                    ->options(function () {
                        return Coa::query()
                            ->whereIn('type', ['expense', 'asset'])
                            ->where('code', '!=', config('accounting.default_ar', '1300'))
                            ->get()
                            ->mapWithKeys(fn (Coa $coa) => [$coa->id => "{$coa->code} - {$coa->name}"]);
                    })
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('total_amount')
                    ->label('Total Tagihan')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->required(),

                DatePicker::make('bill_date')
                    ->label('Tanggal Tagihan')
                    ->default(now())
                    ->required(),

                DatePicker::make('due_date')
                    ->label('Jatuh Tempo')
                    ->default(now()->addDays(14)),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'DRAFT' => 'Draft',
                        'UNPAID' => 'Belum Dibayar (Unpaid)',
                        'PAID' => 'Lunas (Paid)',
                    ])
                    ->default('DRAFT')
                    ->required(),

                Textarea::make('notes')
                    ->label('Catatan / Keterangan')
                    ->placeholder('Keterangan tagihan (contoh: Biaya servis rutin armada truk, suku cadang, dll)')
                    ->columnSpanFull(),
            ]);
    }
}
