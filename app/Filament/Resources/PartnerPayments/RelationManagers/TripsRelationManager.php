<?php

namespace App\Filament\Resources\PartnerPayments\RelationManagers;

use App\Models\PartnerPayment;
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

    protected static ?string $title = 'Daftar Pengiriman Mitra (Trips)';

    protected static ?string $recordTitleAttribute = 'trip_number';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            // Form schema jika mengedit trip langsung
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

                TextColumn::make('total_revenue')
                    ->label('Total Harga ke Pelanggan')
                    ->money('IDR')
                    ->state(fn (Trip $record) => $record->total_revenue),

                TextColumn::make('mitra_share_amount')
                    ->label('Hak Mitra')
                    ->money('IDR')
                    ->sortable(),

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
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        /** @var PartnerPayment $owner */
                        $owner = $this->getOwnerRecord();
                        $investorId = $owner->investor_id;

                        return $query
                            ->whereIn('status', ['SELESAI', 'Selesai', 'selesai', 'Completed', 'completed'])
                            ->whereNull('partner_payment_id')
                            ->whereHas('vehicle', function (Builder $vehicleQuery) use ($investorId) {
                                $vehicleQuery->where('investor_id', $investorId);
                            });
                    })
                    ->multiple()
                    ->after(function () {
                        /** @var PartnerPayment $owner */
                        $owner = $this->getOwnerRecord();
                        $owner->recalculateAmount();
                    }),
            ])
            ->recordActions([
                DissociateAction::make()
                    ->label('Lepas Trip')
                    ->after(function () {
                        /** @var PartnerPayment $owner */
                        $owner = $this->getOwnerRecord();
                        $owner->recalculateAmount();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make()
                        ->label('Lepas Terpilih')
                        ->after(function () {
                            /** @var PartnerPayment $owner */
                            $owner = $this->getOwnerRecord();
                            $owner->recalculateAmount();
                        }),
                ]),
            ]);
    }
}
