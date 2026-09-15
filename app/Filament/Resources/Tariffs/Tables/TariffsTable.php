<?php

namespace App\Filament\Resources\Tariffs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TariffsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Skema Tarif')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_price')
                    ->label('Harga ke Pelanggan')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 2, ',', '.'))
                    ->sortable(),
                TextColumn::make('fee_type')
                    ->label('Tipe Skema')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'fixed' => 'Potongan Tetap (Rp)',
                        'percentage' => 'Bagi Hasil (%)',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'primary',
                        'percentage' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('pt_margin')
                    ->label('Margin PT')
                    ->formatStateUsing(fn ($record) => $record->fee_type === 'percentage'
                        ? number_format($record->pt_margin, 0).'%'
                        : 'Rp '.number_format($record->pt_margin, 2, ',', '.'))
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
