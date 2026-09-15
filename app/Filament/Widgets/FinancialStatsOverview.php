<?php

namespace App\Filament\Widgets;

use App\Models\BankCash;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalKasBank = BankCash::sum('current_balance');
        $piutangBelumTertagih = Invoice::whereIn('status', ['unpaid', 'partial'])->sum('total_amount');
        $totalInvoiceBulanIni = Invoice::whereMonth('issue_date', now()->month)->count();

        return [
            Stat::make('Total Kas & Bank', 'Rp '.number_format($totalKasBank, 0, ',', '.'))
                ->description('Saldo kas dan bank saat ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Piutang Belum Tertagih', 'Rp '.number_format($piutangBelumTertagih, 0, ',', '.'))
                ->description('Total tagihan belum lunas')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Total Invoice Bulan Ini', $totalInvoiceBulanIni)
                ->description('Invoice terbit bulan ini')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
        ];
    }
}
