<?php

namespace App\Filament\Resources\Bills\Tables;

use App\Models\BankCash;
use App\Models\Bill;
use App\Models\Coa;
use App\Models\Journal;
use App\Models\JournalDetail;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class BillsTable
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
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bill_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total Tagihan')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('remaining_amount')
                    ->label('Sisa Tagihan')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match (strtolower($state)) {
                        'paid' => 'Paid (Lunas)',
                        'partial' => 'Partial (Sebagian)',
                        default => 'Unpaid (Belum Dibayar)',
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('payBill')
                    ->label('Bayar Tagihan')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->hidden(fn (Bill $record) => strtolower($record->status) === 'paid')
                    ->form([
                        DatePicker::make('payment_date')
                            ->label('Tanggal Pembayaran')
                            ->default(now())
                            ->required(),

                        TextInput::make('amount_paid')
                            ->label('Nominal Pembayaran')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(fn (Bill $record) => $record->remaining_amount)
                            ->maxValue(fn (Bill $record) => $record->remaining_amount)
                            ->helperText(fn (Bill $record) => 'Sisa tagihan saat ini: Rp '.number_format($record->remaining_amount, 0, ',', '.'))
                            ->required(),

                        Select::make('bank_cash_id')
                            ->label('Sumber Kas / Bank')
                            ->options(BankCash::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function (array $data, Bill $record) {
                        DB::transaction(function () use ($data, $record) {
                            $remaining = $record->remaining_amount;
                            $amountPaid = (float) $data['amount_paid'];

                            // 1. Validasi nominal agar amount_paid tidak melebihi sisa tagihan
                            if ($amountPaid <= 0) {
                                throw new \Exception('Nominal pembayaran harus lebih besar dari 0.');
                            }

                            if ($amountPaid > $remaining + 0.01) {
                                throw new \Exception('Nominal pembayaran (Rp '.number_format($amountPaid, 0, ',', '.').') melebihi sisa tagihan (Rp '.number_format($remaining, 0, ',', '.').').');
                            }

                            // 2. Update paid_amount & status Bill
                            $newPaidAmount = (float) $record->paid_amount + $amountPaid;
                            $newStatus = ($newPaidAmount >= (float) $record->total_amount) ? 'Paid' : 'Partial';

                            $record->update([
                                'paid_amount' => $newPaidAmount,
                                'status' => $newStatus,
                            ]);

                            // 3. AUTOMASI JURNAL DOUBLE-ENTRY
                            $bankCash = BankCash::findOrFail($data['bank_cash_id']);

                            // Cari CoA Hutang Vendor (default AP = 2100)
                            $apCoaCode = config('accounting.default_ap', '2100');
                            $apCoa = Coa::where('code', $apCoaCode)->first()
                                ?? Coa::where('type', 'liability')->where('name', 'like', '%Hutang%')->firstOrFail();

                            $journal = Journal::create([
                                'journal_number' => 'JRN-BPAY-'.strtoupper(uniqid()),
                                'date' => $data['payment_date'] ?? now(),
                                'reference' => 'Tagihan #'.$record->bill_number,
                                'description' => 'Pembayaran Hutang Vendor: '.$record->vendor->name.' (Tagihan '.$record->bill_number.')',
                                'status' => 'approved',
                                'created_by' => auth()->id(),
                            ]);

                            // Sisi DEBIT: Hutang Vendor (Hutang berkurang di sisi Debit)
                            JournalDetail::create([
                                'journal_id' => $journal->id,
                                'coa_id' => $apCoa->id,
                                'debit' => $amountPaid,
                                'credit' => 0,
                                'description' => 'Pelunasan Hutang Vendor: '.$record->vendor->name,
                            ]);

                            // Sisi KREDIT: Akun Kas / Bank (Kas berkurang di sisi Kredit)
                            JournalDetail::create([
                                'journal_id' => $journal->id,
                                'coa_id' => $bankCash->coa_id,
                                'debit' => 0,
                                'credit' => $amountPaid,
                                'description' => 'Pengeluaran Kas/Bank via '.$bankCash->name.' untuk Tagihan #'.$record->bill_number,
                            ]);

                            // Kurangi saldo rekening Kas / Bank
                            $bankCash->decrement('current_balance', $amountPaid);

                            Notification::make()
                                ->title('Pembayaran Tagihan Berhasil Dicatat')
                                ->body('Pembayaran sebesar Rp '.number_format($amountPaid, 0, ',', '.').' untuk tagihan '.$record->bill_number.' berhasil diproses dan dijurnal.')
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
