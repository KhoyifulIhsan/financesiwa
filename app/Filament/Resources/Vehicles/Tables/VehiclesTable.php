<?php

namespace App\Filament\Resources\Vehicles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license_plate')->label('Plat Nomor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('capacity')->label('Kapasitas (KL)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('driver_name')->label('Nama Sopir')
                    ->searchable(),
                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('ownership_status')
                    ->label('Kepemilikan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Milik PT' => 'success',
                        'Milik Mitra' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('investor.name')
                    ->label('Mitra / Investor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
