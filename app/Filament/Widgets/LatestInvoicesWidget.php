<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestInvoicesWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()->latest()->limit(5)
            )
            ->columns([
                TextColumn::make('invoice_number')->label('No. Tagihan')
                    ->searchable(),
                TextColumn::make('customer.name')->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_amount')->label('Total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                        'partial' => 'warning',
                        'canceled' => 'gray',
                        default => 'primary',
                    }),
            ])
            ->paginated(false);
    }
}
