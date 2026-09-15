<?php

namespace App\Filament\Resources\Trips\Tables;

use App\Models\BankCash;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Trip;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class TripsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trip_number')
                    ->label('No. Trip')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('vehicle.license_plate')
                    ->label('Plat Armada')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('driver.name')
                    ->label('Sopir')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tariff.name')
                    ->label('Skema Tarif')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('route')
                    ->label('Rute')
                    ->state(fn (Trip $record) => "{$record->route_origin} → {$record->route_destination}"),

                TextColumn::make('volume')
                    ->label('Muatan')
                    ->suffix(' KL')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completed' => 'success',
                        'In Transit' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('trip_expenses_sum_amount')
                    ->label('Total Biaya')
                    ->money('IDR')
                    ->state(fn (Trip $record) => $record->total_expenses)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('completeTrip')
                    ->label('Selesaikan Trip & Posting Biaya')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->hidden(fn (Trip $record) => $record->status === 'Completed')
                    ->form([
                        Select::make('bank_cash_id')
                            ->label('Sumber Kas / Bank (Pembayaran Biaya)')
                            ->options(BankCash::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('journal_date')
                            ->label('Tanggal Jurnal')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (array $data, Trip $record) {
                        DB::transaction(function () use ($data, $record) {
                            // 1. Ubah status Trip menjadi Completed
                            $record->update(['status' => 'Completed']);

                            $expenses = $record->tripExpenses;
                            $totalExpenses = (float) $expenses->sum('amount');

                            // 2. Jika ada biaya, buat Jurnal Transaksi
                            if ($totalExpenses > 0) {
                                $bankCash = BankCash::findOrFail($data['bank_cash_id']);

                                $journal = Journal::create([
                                    'journal_number' => 'JRN-TRIP-'.strtoupper(uniqid()),
                                    'date' => $data['journal_date'] ?? now(),
                                    'reference' => 'Trip #'.$record->trip_number,
                                    'description' => 'Biaya Operasional Pengiriman '.$record->trip_number.' ('.$record->route_origin.' -> '.$record->route_destination.')',
                                    'status' => 'approved',
                                    'created_by' => auth()->id(),
                                ]);

                                // 3. KREDIT: Kas / Bank senilai TOTAL AMOUNT biaya
                                JournalDetail::create([
                                    'journal_id' => $journal->id,
                                    'coa_id' => $bankCash->coa_id,
                                    'debit' => 0,
                                    'credit' => $totalExpenses,
                                    'description' => 'Biaya Operasional Trip '.$record->trip_number.' via '.$bankCash->name,
                                ]);

                                // Kurangi saldo akun Kas / Bank terkait
                                $bankCash->decrement('current_balance', $totalExpenses);

                                // 4. DEBIT: Looping rincian beban operasional di repeater tripExpenses
                                foreach ($expenses as $expense) {
                                    if ($expense->amount > 0) {
                                        JournalDetail::create([
                                            'journal_id' => $journal->id,
                                            'coa_id' => $expense->coa_id,
                                            'debit' => $expense->amount,
                                            'credit' => 0,
                                            'description' => $expense->description ?: ('Biaya Operasional Trip '.$record->trip_number),
                                        ]);
                                    }
                                }
                            }

                            // LOGIKA BAGI HASIL MITRA (REVENUE SHARING)
                            $trip = $record;
                            $vehicle = $trip->vehicle;
                            $tariff = $trip->tariff;

                            if ($vehicle && $vehicle->ownership_status === 'Milik Mitra' && $tariff) {
                                $volume = $trip->volume;
                                $total_revenue = $volume * $tariff->customer_price;
                                $mitra_share = 0;

                                // Kalkulasi Hak Mitra berdasarkan Tipe Tarif
                                if ($tariff->fee_type === 'fixed') {
                                    $mitra_share_per_unit = $tariff->customer_price - $tariff->pt_margin;
                                    $mitra_share = $volume * $mitra_share_per_unit;
                                } elseif ($tariff->fee_type === 'percentage') {
                                    $pt_share = $total_revenue * ($tariff->pt_margin / 100);
                                    $mitra_share = $total_revenue - $pt_share;
                                }

                                if ($mitra_share > 0) {
                                    // Pastikan CoA untuk Bagi Hasil & Hutang Mitra tersedia (Otomatis buat jika belum ada)
                                    $coaBebanBagiHasil = Coa::firstOrCreate(
                                        ['code' => '5500'],
                                        ['name' => 'Beban Bagi Hasil Mitra', 'type' => 'expense', 'is_active' => true]
                                    );
                                    $coaHutangMitra = Coa::firstOrCreate(
                                        ['code' => '2150'],
                                        ['name' => 'Hutang Mitra / Investor', 'type' => 'liability', 'is_active' => true]
                                    );

                                    $investorName = $vehicle->investor ? $vehicle->investor->name : 'Mitra';

                                    // Buat Jurnal Bagi Hasil
                                    $jurnalBagiHasil = Journal::create([
                                        'journal_number' => 'BH-'.$trip->trip_number.'-'.time(),
                                        'date' => now(),
                                        'reference' => 'Trip #'.$trip->trip_number,
                                        'description' => 'Bagi hasil mitra: '.$investorName.' (Trip '.$trip->trip_number.')',
                                        'status' => 'Posted',
                                        'created_by' => auth()->id(),
                                    ]);

                                    // Debit: Beban Bagi Hasil
                                    JournalDetail::create([
                                        'journal_id' => $jurnalBagiHasil->id,
                                        'coa_id' => $coaBebanBagiHasil->id,
                                        'debit' => $mitra_share,
                                        'credit' => 0,
                                        'description' => 'Beban Bagi Hasil Trip '.$trip->trip_number,
                                    ]);

                                    // Kredit: Hutang Mitra
                                    JournalDetail::create([
                                        'journal_id' => $jurnalBagiHasil->id,
                                        'coa_id' => $coaHutangMitra->id,
                                        'debit' => 0,
                                        'credit' => $mitra_share,
                                        'description' => 'Hutang Bagi Hasil Mitra '.$investorName,
                                    ]);
                                }
                            }

                            Notification::make()
                                ->title('Trip Selesai & Biaya Diposting')
                                ->body("Trip {$record->trip_number} telah diselesaikan dan entri jurnal biaya operasional berhasil dicatat.")
                                ->success()
                                ->send();
                        });
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
