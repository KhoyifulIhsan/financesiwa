<x-filament-panels::page>
    <style>
        .report-form-container { margin-bottom: 1.5rem; }
        .report-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left; }
        .report-table th, .report-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
        .dark .report-table th, .dark .report-table td { border-bottom: 1px solid #374151; }
        .report-table thead tr { background-color: #f9fafb; }
        .dark .report-table thead tr { background-color: #1f2937; }
        .report-table th { font-weight: 600; color: #374151; }
        .dark .report-table th { color: #d1d5db; }
        .report-table .text-right { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .report-table tfoot tr { background-color: #f9fafb; font-weight: 700; border-top: 2px solid #d1d5db; }
        .dark .report-table tfoot tr { background-color: #1f2937; border-top: 2px solid #4b5563; }
        .report-empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1.5rem; text-align: center; color: #6b7280; }
        .report-empty-icon { width: 3.5rem; height: 3.5rem; max-width: 3.5rem; max-height: 3.5rem; margin-bottom: 1rem; color: #9ca3af; }
    </style>

    <div class="report-form-container">
        <form wire:submit="submit">
            {{ $this->form }}
        </form>
    </div>
    
    <x-filament::section>
        @if(empty($this->ledgerData) || count($this->ledgerData) === 0)
            <div class="report-empty-state">
                <svg class="report-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <p class="text-sm font-medium">Tidak ada transaksi pada periode ini atau belum memilih akun.</p>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Tanggal</th>
                            <th style="width: 20%;">No. Jurnal</th>
                            <th style="width: 35%;">Keterangan</th>
                            <th class="text-right" style="width: 15%;">Debit</th>
                            <th class="text-right" style="width: 15%;">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalDebit = 0;
                            $totalCredit = 0;
                        @endphp
                        @foreach($this->ledgerData as $detail)
                            @php
                                $totalDebit += $detail->debit;
                                $totalCredit += $detail->credit;
                            @endphp
                            <tr>
                                <td>{{ $detail->journal?->date ? \Carbon\Carbon::parse($detail->journal->date)->format('d/m/Y') : '-' }}</td>
                                <td style="font-weight: 500;">
                                    {{ $detail->journal?->journal_number ?? '-' }}
                                    @if($detail->journal?->reference)
                                        <span style="display: block; font-size: 0.75rem; color: #6b7280; font-weight: normal;">Ref: {{ $detail->journal->reference }}</span>
                                    @endif
                                </td>
                                <td>{{ $detail->description ?? $detail->journal?->description ?? '-' }}</td>
                                <td class="text-right" style="font-weight: 500;">
                                    {{ $detail->debit > 0 ? 'Rp ' . number_format($detail->debit, 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-right" style="font-weight: 500;">
                                    {{ $detail->credit > 0 ? 'Rp ' . number_format($detail->credit, 0, ',', '.') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right" style="text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Total Mutasi</td>
                            <td class="text-right">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($totalCredit, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
