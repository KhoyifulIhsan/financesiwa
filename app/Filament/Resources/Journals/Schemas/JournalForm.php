<?php

namespace App\Filament\Resources\Journals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class JournalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('journal_number')->label('Nomor Jurnal')
                    ->required()
                    ->unique(ignoreRecord: true),
                DatePicker::make('date')->label('Tanggal')
                    ->required(),
                TextInput::make('reference')->label('Referensi'),
                Textarea::make('description')->label('Deskripsi')
                    ->columnSpanFull(),
                Select::make('status')->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'approved' => 'Disetujui',
                    ])
                    ->default('draft')
                    ->required(),
                Repeater::make('details')
                    ->label('Detail Jurnal')
                    ->relationship()
                    ->schema([
                        Select::make('coa_id')->label('Akun (CoA)')
                            ->relationship('coa', 'name')
                            ->searchable()
                            ->required(),
                        TextInput::make('description')->label('Deskripsi'),
                        TextInput::make('debit')->label('Nominal Debit')
                            ->numeric()
                            ->default(0),
                        TextInput::make('credit')->label('Nominal Kredit')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->rules([
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                $totalDebit = collect($value)->sum('debit');
                                $totalCredit = collect($value)->sum('credit');
                                if (abs($totalDebit - $totalCredit) > 0.01) {
                                    $fail('Transaksi gagal disimpan: Total Debit dan Kredit harus seimbang (Balance)');
                                }
                            };
                        },
                    ])
                    ->required(),
            ]);
    }
}
