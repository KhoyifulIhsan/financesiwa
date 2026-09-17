<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Widgets\ChartWidget;

class TripStatusChart extends ChartWidget
{
    protected ?string $heading = 'Rasio Status Pengiriman';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $draftCount = Trip::whereIn('status', ['DRAFT', 'draft', 'Pending', 'pending'])->count();

        $processCount = Trip::whereIn('status', [
            'PROSES', 'proses',
            'JALAN', 'jalan',
            'In Transit', 'in transit',
            'In Progress', 'in progress',
        ])->count();

        $completedCount = Trip::whereIn('status', [
            'SELESAI', 'selesai',
            'Completed', 'completed',
        ])->count();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Trip',
                    'data' => [$draftCount, $processCount, $completedCount],
                    'backgroundColor' => [
                        '#9ca3af', // Gray untuk Draft / Pending
                        '#3b82f6', // Biru untuk Proses / Dalam Perjalanan
                        '#10b981', // Hijau untuk Selesai
                    ],
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => [
                'Draft / Pending',
                'Proses / Jalan',
                'Selesai',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
