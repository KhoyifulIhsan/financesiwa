<?php

namespace App\Filament\Resources\BankCashes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankCashesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('coa.name')->label('Akun (CoA)')
                    ->sortable(),
                TextColumn::make('name')->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')->label('Tipe')
                    ->badge()
                    ->sortable(),
                TextColumn::make('account_number')->label('Nomor Rekening')
                    ->searchable(),
                TextColumn::make('current_balance')->label('Saldo Saat Ini')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
