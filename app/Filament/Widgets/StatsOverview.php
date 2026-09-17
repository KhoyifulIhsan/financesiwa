<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\PartnerPayment;
use App\Models\Trip;
use App\Models\VendorBill;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. Total Piutang (Unpaid Invoices)
        $totalPiutang = (float) Invoice::whereIn('status', ['UNPAID', 'unpaid'])->sum('total_amount');

        // 2. Total Hutang Mitra & Vendor
        $hutangMitra = 0;
        if (Schema::hasColumn('partner_payments', 'status')) {
            $hutangMitra = (float) PartnerPayment::whereIn('status', ['DRAFT', 'draft'])->sum('amount');
        } else {
            // Hak mitra dari trip selesai yang belum dicairkan (partner_payment_id masih null)
            $hutangMitra = (float) Trip::whereNull('partner_payment_id')
                ->whereIn('status', ['Completed', 'SELESAI', 'Selesai', 'completed'])
                ->sum('mitra_share_amount');
        }

        $hutangVendor = (float) VendorBill::whereIn('status', ['UNPAID', 'unpaid'])->sum('total_amount');
        $totalHutang = $hutangMitra + $hutangVendor;

        // 3. Trip Sedang Berjalan (Status bukan SELESAI atau DRAFT)
        $activeTrips = Trip::whereNotIn('status', [
            'SELESAI', 'selesai', 'Completed', 'completed',
            'DRAFT', 'draft',
        ])->count();

        return [
            Stat::make('Total Piutang (Unpaid Invoices)', 'Rp '.number_format($totalPiutang, 0, ',', '.'))
                ->description('Menunggu pembayaran pelanggan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning'),

            Stat::make('Total Hutang Mitra & Vendor', 'Rp '.number_format($totalHutang, 0, ',', '.'))
                ->description('Kewajiban pembayaran berjalan')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Trip Sedang Berjalan', number_format($activeTrips, 0, ',', '.').' Trip')
                ->description('Armada aktif beroperasi')
                ->descriptionIcon('heroicon-m-truck')
                ->color('success'),
        ];
    }
}
