<?php

namespace App\Filament\Resources\Investors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvestorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Mitra / Investor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Nomor Telepon')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('bank_account_info')
                    ->label('Info Rekening Bank')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('vehicles_count')
                    ->counts('vehicles')
                    ->label('Jumlah Truk')
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
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
