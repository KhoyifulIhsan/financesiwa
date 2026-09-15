<x-filament-panels::page>
    <style>
        .report-form-container { margin-bottom: 1.5rem; }
        .pl-section { margin-bottom: 2rem; }
        .pl-title { font-size: 1.125rem; font-weight: 700; margin-bottom: 0.75rem; color: #111827; }
        .dark .pl-title { color: #f9fafb; }
        .report-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left; }
        .report-table td, .report-table th { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
        .dark .report-table td, .dark .report-table th { border-bottom: 1px solid #374151; }
        .report-table .text-right { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .report-table tfoot tr { font-weight: 700; background-color: #f9fafb; border-top: 2px solid #d1d5db; }
        .dark .report-table tfoot tr { background-color: #1f2937; border-top: 2px solid #4b5563; }
        .text-success { color: #16a34a !important; }
        .dark .text-success { color: #4ade80 !important; }
        .text-danger { color: #dc2626 !important; }
        .dark .text-danger { color: #f87171 !important; }
        .net-profit-box { margin-top: 1.5rem; padding: 1.25rem 1.5rem; border-radius: 0.75rem; border: 2px solid #e5e7eb; background-color: #f9fafb; display: flex; justify-content: space-between; align-items: center; }
        .dark .net-profit-box { border-color: #374151; background-color: #1f2937; }
        .net-profit-box h2 { font-size: 1.25rem; font-weight: 700; margin: 0; }
        .net-profit-box .amount { font-size: 1.35rem; font-weight: 800; }
    </style>

    <div class="report-form-container">
        <form wire:submit="submit">
            {{ $this->form }}
        </form>
    </div>

    <x-filament::section>
        <div>
            <!-- PENDAPATAN -->
            <div class="pl-section">
                <h3 class="pl-title">Pendapatan (Revenue)</h3>
                <table class="report-table">
                    <tbody>
                        @forelse($revenues ?? [] as $rev)
                        <tr>
                            <td style="width: 70%;">
                                <span style="font-family: monospace; color: #6b7280; margin-right: 0.5rem;">{{ $rev['code'] }}</span>
                                <span style="font-weight: 500;">{{ $rev['name'] }}</span>
                            </td>
                            <td class="text-right" style="width: 30%; font-weight: 500;">
                                Rp {{ number_format($rev['balance'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" style="text-align: center; color: #9ca3af; padding: 1.5rem;">
                                Tidak ada akun pendapatan terdaftar.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td style="text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.025em;">Total Pendapatan</td>
                            <td class="text-right text-success" style="font-size: 1rem;">
                                Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- BEBAN -->
            <div class="pl-section">
                <h3 class="pl-title">Beban Operasional (Expense)</h3>
                <table class="report-table">
                    <tbody>
                        @forelse($expenses ?? [] as $exp)
                        <tr>
                            <td style="width: 70%;">
                                <span style="font-family: monospace; color: #6b7280; margin-right: 0.5rem;">{{ $exp['code'] }}</span>
                                <span style="font-weight: 500;">{{ $exp['name'] }}</span>
                            </td>
                            <td class="text-right" style="width: 30%; font-weight: 500;">
                                Rp {{ number_format($exp['balance'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" style="text-align: center; color: #9ca3af; padding: 1.5rem;">
                                Tidak ada akun beban terdaftar.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td style="text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.025em;">Total Beban</td>
                            <td class="text-right text-danger" style="font-size: 1rem;">
                                Rp {{ number_format($totalExpense ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- LABA / RUGI BERSIH -->
            <div class="net-profit-box">
                <h2>Laba / Rugi Bersih</h2>
                <span class="amount {{ ($netProfit ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($netProfit ?? 0, 0, ',', '.') }}
                </span>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
