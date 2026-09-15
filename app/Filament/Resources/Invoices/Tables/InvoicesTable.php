<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\BankCash;
use App\Models\Coa;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Payment;
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

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('No. Tagihan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice_date')
                    ->label('Tanggal Invoice')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('total_amount')
                    ->label('Total Tagihan')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtoupper($state)) {
                        'PAID' => 'success',
                        'UNPAID' => 'warning',
                        default => 'gray',
                    }),

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

                Action::make('publishInvoice')
                    ->label('Terbitkan (UNPAID)')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (Invoice $record) => strtoupper($record->status) === 'DRAFT')
                    ->requiresConfirmation()
                    ->modalHeading('Terbitkan Tagihan')
                    ->modalDescription('Apakah Anda yakin ingin menerbitkan tagihan ini? Sistem akan mencatat piutang dan pendapatan ke Jurnal Umum otomatis.')
                    ->action(function (Invoice $record) {
                        $record->update(['status' => 'UNPAID']);

                        Notification::make()
                            ->title('Invoice Diterbitkan')
                            ->body("Invoice {$record->invoice_number} telah berstatus UNPAID dan dijurnal ke Piutang Usaha.")
                            ->success()
                            ->send();
                    }),

                Action::make('terima_pembayaran')
                    ->label('Terima Pembayaran')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn (Invoice $record) => strtoupper((string) $record->status) === 'UNPAID')
                    ->form([
                        DatePicker::make('payment_date')
                            ->label('Tanggal Pembayaran')
                            ->required()
                            ->default(now()),

                        Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'Transfer Bank' => 'Transfer Bank',
                                'Cash' => 'Cash / Tunai',
                                'Giro / Cek' => 'Giro / Cek',
                            ])
                            ->default('Transfer Bank'),

                        Select::make('bank_account_id')
                            ->label('Akun Kas / Bank')
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

                        TextInput::make('amount_paid')
                            ->label('Nominal Pembayaran')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(fn (Invoice $record) => $record->total_amount)
                            ->required(),
                    ])
                    ->action(function (array $data, Invoice $record) {
                        DB::transaction(function () use ($data, $record) {
                            $amountPaid = (float) $data['amount_paid'];

                            // 1. Update tabel invoices: status menjadi PAID
                            $record->update([
                                'status' => 'PAID',
                            ]);

                            // 2. Buat Jurnal Pelunasan
                            $arCoaCode = config('accounting.default_ar', '1300');
                            $coaPiutang = Coa::firstOrCreate(
                                ['code' => $arCoaCode],
                                ['name' => 'Piutang Usaha', 'type' => 'asset', 'is_active' => true]
                            );

                            $journalNumber = 'PAY-'.$record->invoice_number;
                            if (Journal::where('journal_number', $journalNumber)->exists()) {
                                $journalNumber .= '-'.strtoupper(substr(uniqid(), -4));
                            }

                            $journal = Journal::create([
                                'journal_number' => $journalNumber,
                                'date' => $data['payment_date'],
                                'reference' => 'Pelunasan Inv #'.$record->invoice_number,
                                'description' => 'Penerimaan Pembayaran Invoice '.$record->invoice_number.' - '.($record->customer?->name ?? 'Pelanggan'),
                                'status' => 'approved',
                                'created_by' => auth()->id(),
                            ]);

                            // Debit: Rekening Kas/Bank yang dipilih user
                            JournalDetail::create([
                                'journal_id' => $journal->id,
                                'coa_id' => $data['bank_account_id'],
                                'debit' => $amountPaid,
                                'credit' => 0,
                                'description' => 'Penerimaan Kas/Bank atas Pelunasan Invoice #'.$record->invoice_number,
                            ]);

                            // Kredit: Piutang Usaha
                            JournalDetail::create([
                                'journal_id' => $journal->id,
                                'coa_id' => $coaPiutang->id,
                                'debit' => 0,
                                'credit' => $amountPaid,
                                'description' => 'Pelunasan Piutang Usaha atas Invoice #'.$record->invoice_number,
                            ]);

                            // Sinkronisasi saldo rekening kas/bank & payment jika terhubung ke data BankCash
                            $bankCash = BankCash::where('coa_id', $data['bank_account_id'])->first();
                            if ($bankCash) {
                                $bankCash->increment('current_balance', $amountPaid);

                                Payment::create([
                                    'invoice_id' => $record->id,
                                    'bank_cash_id' => $bankCash->id,
                                    'payment_date' => $data['payment_date'],
                                    'amount' => $amountPaid,
                                    'reference' => $journalNumber,
                                ]);
                            }

                            // 3. Tampilkan Notifikasi Sukses Filament
                            Notification::make()
                                ->success()
                                ->title('Pembayaran berhasil dicatat')
                                ->body("Invoice {$record->invoice_number} berhasil dilunasi.")
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
