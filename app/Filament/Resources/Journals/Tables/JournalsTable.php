<?php

namespace App\Filament\Resources\Journals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JournalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('journal_number')->label('Nomor Jurnal')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('reference')->label('Referensi')
                    ->searchable(),
                TextColumn::make('description')->label('Deskripsi')
                    ->limit(40),
                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->sortable(),
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
