<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\BankCash;
use App\Models\Coa;
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
                TextColumn::make('invoice_number')->label('No. Tagihan')
                    ->searchable(),
                TextColumn::make('customer.name')->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('issue_date')->label('Tanggal Terbit')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')->label('Jatuh Tempo')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_amount')->label('Total Tagihan')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('amount_due')->label('Sisa Tagihan')
                    ->money('IDR'),
                TextColumn::make('status')->label('Status')
                    ->badge(),
                TextColumn::make('created_at')->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('registerPayment')
                    ->label('Catat Pembayaran')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->hidden(fn ($record) => $record->status === 'paid' || $record->status === 'canceled')
                    ->form([
                        DatePicker::make('payment_date')->label('Tanggal Pembayaran')
                            ->required()
                            ->default(now()),
                        TextInput::make('amount_paid')->label('Nominal Dibayar')
                            ->numeric()
                            ->required()
                            ->maxValue(fn ($record) => $record->amount_due),
                        Select::make('bank_cash_id')->label('Akun Kas/Bank')
                            ->options(BankCash::pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (array $data, $record) {
                        DB::beginTransaction();
                        try {
                            if ($data['amount_paid'] > $record->amount_due) {
                                throw new \Exception('Nominal melebihi sisa tagihan.');
                            }

                            Payment::create([
                                'invoice_id' => $record->id,
                                'bank_cash_id' => $data['bank_cash_id'],
                                'payment_date' => $data['payment_date'],
                                'amount' => $data['amount_paid'],
                            ]);

                            $amountPaid = $data['amount_paid'];
                            $newAmountDue = $record->amount_due - $amountPaid;
                            $record->update([
                                'status' => $newAmountDue <= 0 ? 'paid' : 'partial',
                            ]);

                            $journal = Journal::create([
                                'journal_number' => 'JRN-PAY-'.strtoupper(uniqid()),
                                'date' => $data['payment_date'],
                                'reference' => 'Pembayaran Inv #'.$record->invoice_number,
                                'description' => 'Pembayaran dari '.$record->customer->name,
                                'status' => 'approved',
                                'created_by' => auth()->id(),
                            ]);

                            $bankCash = BankCash::find($data['bank_cash_id']);
                            $arCoaCode = config('accounting.default_ar', '1300');
                            $arCoa = Coa::where('code', $arCoaCode)->firstOrFail();

                            JournalDetail::create([
                                'journal_id' => $journal->id,
                                'coa_id' => $bankCash->coa_id,
                                'debit' => $amountPaid,
                                'credit' => 0,
                                'description' => 'Debit Kas/Bank',
                            ]);

                            JournalDetail::create([
                                'journal_id' => $journal->id,
                                'coa_id' => $arCoa->id,
                                'debit' => 0,
                                'credit' => $amountPaid,
                                'description' => 'Kredit Piutang Usaha',
                            ]);

                            $totalDebit = $journal->details()->sum('debit');
                            $totalCredit = $journal->details()->sum('credit');
                            if (abs($totalDebit - $totalCredit) > 0.01) {
                                throw new \Exception('Jurnal tidak seimbang.');
                            }

                            DB::commit();

                            Notification::make()
                                ->title('Pembayaran berhasil dicatat')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Notification::make()
                                ->title('Gagal mencatat pembayaran')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
