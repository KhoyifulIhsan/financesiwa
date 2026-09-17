<?php

namespace App\Filament\Resources\VendorBills\Tables;

use App\Models\BankCash;
use App\Models\Coa;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\VendorBill;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class VendorBillsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bill_number')
                    ->label('No. Tagihan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('vendor.name')
                    ->label('Vendor / Supplier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bill_date')
                    ->label('Tanggal Tagihan')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('expenseAccount.name')
                    ->label('Kategori Biaya')
                    ->searchable(),

                TextColumn::make('total_amount')
                    ->label('Total Tagihan')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtoupper($state)) {
                        'DRAFT' => 'gray',
                        'UNPAID' => 'warning',
                        'PAID' => 'success',
                        default => 'gray',
                    }),

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
            ->defaultSort('bill_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'DRAFT' => 'Draft',
                        'UNPAID' => 'Belum Dibayar (Unpaid)',
                        'PAID' => 'Lunas (Paid)',
                    ]),
                SelectFilter::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name'),
            ])
            ->recordActions([
                static::getConfirmAction(),
                static::getPayBillAction(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getConfirmAction(): Action
    {
        return Action::make('confirm_unpaid')
            ->label('Konfirmasi (UNPAID)')
            ->icon('heroicon-o-check-badge')
            ->color('info')
            ->visible(fn (VendorBill $record) => strtoupper((string) $record->status) === 'DRAFT')
            ->requiresConfirmation()
            ->modalHeading('Konfirmasi Tagihan Vendor')
            ->modalDescription('Apakah Anda yakin ingin mengonfirmasi tagihan ini menjadi UNPAID? Jurnal pengakuan hutang dan biaya akan otomatis dicatat.')
            ->modalSubmitActionLabel('Ya, Konfirmasi')
            ->action(function (VendorBill $record) {
                $record->update(['status' => 'UNPAID']);

                Notification::make()
                    ->title('Tagihan Dikonfirmasi')
                    ->body('Status tagihan telah diubah menjadi UNPAID dan hutang usaha berhasil dicatat.')
                    ->success()
                    ->send();
            });
    }

    public static function getPayBillAction(): Action
    {
        return Action::make('pay_bill')
            ->label('Bayar Tagihan (Pay Bill)')
            ->icon('heroicon-o-currency-dollar')
            ->color('success')
            ->visible(fn (VendorBill $record) => strtoupper((string) $record->status) === 'UNPAID')
            ->form([
                DatePicker::make('payment_date')
                    ->label('Tanggal Pembayaran')
                    ->default(now())
                    ->required(),

                Select::make('payment_account_id')
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

                TextInput::make('reference_number')
                    ->label('No. Referensi / Bukti Transfer')
                    ->placeholder('Contoh: TRF-BCA-9921'),
            ])
            ->action(function (array $data, VendorBill $record) {
                DB::transaction(function () use ($data, $record) {
                    $totalAmount = (float) $record->total_amount;

                    // 1. Ubah status menjadi PAID
                    $record->update(['status' => 'PAID']);

                    // 2. Pastikan akun Hutang Usaha (AP) tersedia
                    $apCoaCode = config('accounting.default_ap', '2100');
                    $coaHutangUsaha = Coa::firstOrCreate(
                        ['code' => $apCoaCode],
                        [
                            'name' => 'Hutang Usaha',
                            'type' => 'liability',
                            'is_active' => true,
                        ]
                    );

                    // 3. Buat Jurnal Pelunasan Hutang
                    $vendorName = $record->vendor?->name ?? 'Vendor';
                    $journalNumber = 'PAY-BILL-'.$record->id.'-'.time();
                    if (Journal::where('journal_number', $journalNumber)->exists()) {
                        $journalNumber .= '-'.strtoupper(substr(uniqid(), -4));
                    }

                    $journal = Journal::create([
                        'journal_number' => $journalNumber,
                        'date' => $data['payment_date'],
                        'reference' => 'Pelunasan Tagihan #'.$record->bill_number,
                        'description' => 'Pembayaran Tagihan Vendor '.$record->bill_number.' - '.$vendorName.(! empty($data['reference_number']) ? ' (Ref: '.$data['reference_number'].')' : ''),
                        'status' => 'approved',
                        'created_by' => auth()->id(),
                    ]);

                    // DEBIT: Hutang Usaha (Hutang berkurang di Debit)
                    JournalDetail::create([
                        'journal_id' => $journal->id,
                        'coa_id' => $coaHutangUsaha->id,
                        'debit' => $totalAmount,
                        'credit' => 0,
                        'description' => 'Pelunasan Tagihan Vendor #'.$record->bill_number,
                    ]);

                    // KREDIT: Kas / Bank (Kas berkurang di Kredit)
                    JournalDetail::create([
                        'journal_id' => $journal->id,
                        'coa_id' => $data['payment_account_id'],
                        'debit' => 0,
                        'credit' => $totalAmount,
                        'description' => 'Pengeluaran Kas/Bank untuk Pembayaran Tagihan #'.$record->bill_number,
                    ]);

                    // Sinkronisasi saldo rekening kas/bank jika akun terdaftar di data BankCash
                    $bankCash = BankCash::where('coa_id', $data['payment_account_id'])->first();
                    if ($bankCash) {
                        $bankCash->decrement('current_balance', $totalAmount);
                    }
                });

                Notification::make()
                    ->title('Tagihan Berhasil Dibayar')
                    ->body('Status tagihan telah diubah menjadi PAID dan jurnal pelunasan berhasil dicatat.')
                    ->success()
                    ->send();
            });
    }
}
