<?php

namespace App\Filament\Resources\Coas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CoasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode Akun')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')->label('Tipe')
                    ->badge()
                    ->sortable(),
                TextColumn::make('parent.name')->label('Akun Induk')
                    ->sortable(),
                IconColumn::make('is_active')->label('Status Aktif')
                    ->boolean(),
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
