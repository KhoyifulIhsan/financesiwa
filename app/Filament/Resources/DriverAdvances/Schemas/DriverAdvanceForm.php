<?php

namespace App\Filament\Resources\DriverAdvances\Schemas;

use App\Models\Coa;
use App\Models\Trip;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class DriverAdvanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('trip_id')
                    ->label('Pengiriman (Trip)')
                    ->relationship(
                        name: 'trip',
                        titleAttribute: 'trip_number',
                        modifyQueryUsing: fn (Builder $query) => $query->whereNotIn('status', ['Completed', 'SELESAI', 'selesai'])
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($state) {
                            $trip = Trip::find($state);
                            if ($trip && $trip->driver_id) {
                                $set('driver_id', $trip->driver_id);
                            }
                        }
                    }),

                Select::make('driver_id')
                    ->label('Sopir (Driver)')
                    ->relationship('driver', 'name')
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

                TextInput::make('amount')
                    ->label('Nominal Uang Jalan')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->required(),

                DatePicker::make('issue_date')
                    ->label('Tanggal Pengeluaran')
                    ->default(now())
                    ->required(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'DRAFT' => 'Draft',
                        'DISBURSED' => 'Dicairkan (Disbursed)',
                        'SETTLED' => 'Selesai (Settled)',
                    ])
                    ->default('DRAFT')
                    ->disabled()
                    ->dehydrated(),

                Textarea::make('notes')
                    ->label('Catatan / Keterangan')
                    ->placeholder('Keterangan uang jalan sopir (contoh: tol, konsumsi, solar operasional)')
                    ->columnSpanFull(),
            ]);
    }
}
