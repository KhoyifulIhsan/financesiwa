<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use App\Models\Invoice;
use App\Models\Trip;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TripsRelationManager extends RelationManager
{
    protected static string $relationship = 'trips';

    protected static ?string $title = 'Daftar Pengiriman Terkait (Trips)';

    protected static ?string $recordTitleAttribute = 'trip_number';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            // Form schema jika mengedit data trip langsung
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('trip_number')
            ->columns([
                TextColumn::make('trip_number')
                    ->label('No. Pengiriman (Trip)')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('vehicle.license_plate')
                    ->label('Armada')
                    ->searchable(),

                TextColumn::make('driver.name')
                    ->label('Sopir')
                    ->searchable(),

                TextColumn::make('route')
                    ->label('Rute')
                    ->state(fn (Trip $record) => "{$record->route_origin} → {$record->route_destination}"),

                TextColumn::make('volume')
                    ->label('Muatan')
                    ->suffix(' KL')
                    ->sortable(),

                TextColumn::make('tariff.name')
                    ->label('Skema Tarif')
                    ->placeholder('-'),

                TextColumn::make('tariff.customer_price')
                    ->label('Harga/KL')
                    ->money('IDR')
                    ->placeholder('-'),

                TextColumn::make('total_revenue')
                    ->label('Subtotal Tagihan')
                    ->money('IDR')
                    ->state(fn (Trip $record) => $record->total_revenue),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'completed', 'selesai' => 'success',
                        'in transit' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->headerActions([
                AssociateAction::make()
                    ->label('Kaitkan Trip (Associate)')
                    ->icon('heroicon-o-link')
                    ->preloadRecordSelect()
                    ->recordTitleAttribute('trip_number')
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query
                        ->where('customer_id', $this->getOwnerRecord()->customer_id)
                        ->whereIn('status', ['SELESAI', 'Selesai', 'selesai', 'Completed', 'completed'])
                        ->whereNull('invoice_id')
                    )
                    ->multiple()
                    ->after(function () {
                        /** @var Invoice $invoice */
                        $invoice = $this->getOwnerRecord();
                        $invoice->recalculateTotalAmount();
                    }),
            ])
            ->recordActions([
                DissociateAction::make()
                    ->label('Lepas Trip')
                    ->after(function (RelationManager $livewire) {
                        /** @var Invoice $invoice */
                        $invoice = $livewire->getOwnerRecord();
                        $invoice->recalculateTotalAmount();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make()
                        ->label('Lepas Terpilih')
                        ->after(function (RelationManager $livewire) {
                            /** @var Invoice $invoice */
                            $invoice = $livewire->getOwnerRecord();
                            $invoice->recalculateTotalAmount();
                        }),
                ]),
            ]);
    }
}
