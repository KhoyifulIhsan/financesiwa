<?php

namespace App\Filament\Resources\DriverAdvances\Tables;

use App\Models\BankCash;
use App\Models\Coa;
use App\Models\DriverAdvance;
use App\Models\Journal;
use App\Models\JournalDetail;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class DriverAdvancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trip.trip_number')
                    ->label('No. Trip')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('driver.name')
                    ->label('Nama Sopir')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtoupper($state)) {
                        'DRAFT' => 'gray',
                        'DISBURSED' => 'warning',
                        'SETTLED' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('bankAccount.name')
                    ->label('Sumber Dana')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->label('Tanggal Pengeluaran')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->limit(30),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('issue_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'DRAFT' => 'Draft',
                        'DISBURSED' => 'Dicairkan (Disbursed)',
                        'SETTLED' => 'Selesai (Settled)',
                    ]),
                SelectFilter::make('driver_id')
                    ->label('Sopir')
                    ->relationship('driver', 'name'),
            ])
            ->recordActions([
                static::getDisburseAction(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getDisburseAction(): Action
    {
        return Action::make('disburse')
            ->label('Cairkan Uang (Disburse)')
            ->icon('heroicon-o-banknotes')
            ->color('warning')
            ->visible(fn (DriverAdvance $record) => strtoupper((string) $record->status) === 'DRAFT')
            ->requiresConfirmation()
            ->modalHeading('Cairkan Uang Jalan Sopir')
            ->modalDescription('Apakah Anda yakin ingin mencairkan uang jalan ini? Jurnal pengeluaran kas dan piutang karyawan akan otomatis dicatat.')
            ->modalSubmitActionLabel('Ya, Cairkan Dana')
            ->action(function (DriverAdvance $record) {
                DB::transaction(function () use ($record) {
                    $amount = (float) $record->amount;

                    // 1. Ubah status menjadi DISBURSED
                    $record->update(['status' => 'DISBURSED']);

                    // 2. Pastikan CoA 'Piutang Karyawan / Uang Jalan' tersedia (kode '1140', tipe 'asset')
                    $coaPiutangKaryawan = Coa::firstOrCreate(
                        ['code' => '1140'],
                        [
                            'name' => 'Piutang Karyawan / Uang Jalan',
                            'type' => 'asset',
                            'is_active' => true,
                        ]
                    );

                    // 3. Buat entri Jurnal Pengeluaran Kas
                    $tripNumber = $record->trip?->trip_number ?? ('Trip #'.$record->trip_id);
                    $driverName = $record->driver?->name ?? 'Sopir';

                    $journalNumber = 'UJ-'.$record->id.'-'.time();
                    if (Journal::where('journal_number', $journalNumber)->exists()) {
                        $journalNumber .= '-'.strtoupper(substr(uniqid(), -4));
                    }

                    $journal = Journal::create([
                        'journal_number' => $journalNumber,
                        'date' => $record->issue_date ?? now(),
                        'reference' => 'Uang Jalan #'.$record->id.' ('.$tripNumber.')',
                        'description' => 'Pencairan Uang Jalan: '.$driverName.' - '.$tripNumber.($record->notes ? ' ('.$record->notes.')' : ''),
                        'status' => 'approved',
                        'created_by' => auth()->id(),
                    ]);

                    // 4. DEBIT: 'Piutang Karyawan / Uang Jalan' (1140) sebesar amount
                    JournalDetail::create([
                        'journal_id' => $journal->id,
                        'coa_id' => $coaPiutangKaryawan->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'description' => 'Pemberian Uang Jalan ke '.$driverName.' ('.$tripNumber.')',
                    ]);

                    // 5. KREDIT: Rekening bank_account_id yang dipilih sebesar amount
                    JournalDetail::create([
                        'journal_id' => $journal->id,
                        'coa_id' => $record->bank_account_id,
                        'debit' => 0,
                        'credit' => $amount,
                        'description' => 'Pengeluaran Kas/Bank untuk Uang Jalan '.$driverName,
                    ]);

                    // 6. Sinkronisasi saldo rekening kas/bank jika akun terdaftar di data BankCash
                    $bankCash = BankCash::where('coa_id', $record->bank_account_id)->first();
                    if ($bankCash) {
                        $bankCash->decrement('current_balance', $amount);
                    }
                });

                Notification::make()
                    ->title('Uang Jalan Berhasil Dicairkan')
                    ->body('Status uang jalan telah diubah menjadi DISBURSED dan jurnal pengeluaran kas berhasil dicatat.')
                    ->success()
                    ->send();
            });
    }
}
