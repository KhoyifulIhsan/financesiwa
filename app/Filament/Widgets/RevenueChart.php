<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Tren Pendapatan (6 Bulan Terakhir)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // 6 bulan terakhir sampai bulan berjalan
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = Carbon::now()->subMonths($i);
            $startOfMonth = $monthDate->copy()->startOfMonth()->toDateString();
            $endOfMonth = $monthDate->copy()->endOfMonth()->toDateString();

            $total = (float) Invoice::whereNotIn('status', ['DRAFT', 'draft', 'CANCELED', 'canceled'])
                ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->whereBetween('invoice_date', [$startOfMonth, $endOfMonth])
                        ->orWhere(function ($q) use ($startOfMonth, $endOfMonth) {
                            $q->whereNull('invoice_date')
                                ->whereBetween('created_at', [
                                    $startOfMonth.' 00:00:00',
                                    $endOfMonth.' 23:59:59',
                                ]);
                        });
                })
                ->sum('total_amount');

            $labels[] = $monthDate->translatedFormat('M Y');
            $data[] = $total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Pendapatan (Rp)',
                    'data' => $data,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => 'start',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
