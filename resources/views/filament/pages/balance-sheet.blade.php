<x-filament-panels::page>
    <style>
        .report-form-container { margin-bottom: 1.5rem; }
        .bs-grid { display: grid; grid-template-columns: 1fr; gap: 2rem; }
        @media (min-width: 768px) {
            .bs-grid { grid-template-columns: 1fr 1fr; }
        }
        .bs-column { display: flex; flex-direction: column; }
        .bs-heading { font-size: 1.125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem; color: #4b5563; padding-bottom: 0.5rem; border-bottom: 2px solid #e5e7eb; }
        .dark .bs-heading { color: #9ca3af; border-color: #374151; }
        .bs-subheading { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin: 1.25rem 0 0.5rem; }
        .dark .bs-subheading { color: #9ca3af; }
        .report-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left; }
        .report-table td, .report-table th { padding: 0.65rem 0.75rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
        .dark .report-table td, .dark .report-table th { border-bottom: 1px solid #374151; }
        .report-table .text-right { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .subtotal-row { font-weight: 600; border-top: 1px solid #9ca3af; }
        .total-box { margin-top: auto; padding: 1rem 1.25rem; border-radius: 0.5rem; border-top: 2px solid #111827; background-color: #f9fafb; display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; }
        .dark .total-box { border-top-color: #f9fafb; background-color: #1f2937; }
        .total-box h4 { font-size: 1rem; font-weight: 700; margin: 0; text-transform: uppercase; }
        .total-box .amount { font-size: 1.15rem; font-weight: 800; }
    </style>

    <div class="report-form-container">
        <form wire:submit="submit">
            {{ $this->form }}
        </form>
    </div>

    <x-filament::section>
        <div class="bs-grid">
            
            <!-- KOLOM KIRI: ASET -->
            <div class="bs-column">
                <h3 class="bs-heading">Aset (Aktiva)</h3>
                <table class="report-table">
                    <tbody>
                        @forelse($assets ?? [] as $asset)
                        <tr>
                            <td style="width: 65%;">
                                <span style="font-family: monospace; color: #6b7280; margin-right: 0.5rem;">{{ $asset['code'] }}</span>
                                <span style="font-weight: 500;">{{ $asset['name'] }}</span>
                            </td>
                            <td class="text-right" style="width: 35%; font-weight: 500;">
                                Rp {{ number_format($asset['balance'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" style="text-align: center; color: #9ca3af; padding: 1.5rem;">
                                Tidak ada akun aset terdaftar.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- TOTAL ASET -->
                <div class="total-box">
                    <h4>Total Aset</h4>
                    <span class="amount">Rp {{ number_format($totalAsset ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- KOLOM KANAN: LIABILITAS & EKUITAS -->
            <div class="bs-column">
                <h3 class="bs-heading">Liabilitas & Ekuitas (Pasiva)</h3>
                
                <!-- 1. LIABILITAS -->
                <div class="bs-subheading">Liabilitas</div>
                <table class="report-table">
                    <tbody>
                        @forelse($liabilities ?? [] as $lia)
                        <tr>
                            <td style="width: 65%;">
                                <span style="font-family: monospace; color: #6b7280; margin-right: 0.5rem;">{{ $lia['code'] }}</span>
                                <span>{{ $lia['name'] }}</span>
                            </td>
                            <td class="text-right" style="width: 35%; font-weight: 500;">
                                Rp {{ number_format($lia['balance'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" style="text-align: center; color: #9ca3af; padding: 0.75rem;">
                                Tidak ada liabilitas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="subtotal-row">
                            <td style="font-size: 0.8rem; color: #6b7280;">Subtotal Liabilitas</td>
                            <td class="text-right">Rp {{ number_format($totalLiability ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>

                <!-- 2. EKUITAS -->
                <div class="bs-subheading">Ekuitas</div>
                <table class="report-table">
                    <tbody>
                        @foreach($equities ?? [] as $eq)
                        <tr>
                            <td style="width: 65%;">
                                <span style="font-family: monospace; color: #6b7280; margin-right: 0.5rem;">{{ $eq['code'] }}</span>
                                <span>{{ $eq['name'] }}</span>
                            </td>
                            <td class="text-right" style="width: 35%; font-weight: 500;">
                                Rp {{ number_format($eq['balance'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        <tr>
                            <td style="font-style: italic; color: #6b7280;">Laba / Rugi Berjalan</td>
                            <td class="text-right" style="font-style: italic; font-weight: 600; color: {{ ($netProfit ?? 0) >= 0 ? '#16a34a' : '#dc2626' }};">
                                Rp {{ number_format($netProfit ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="subtotal-row">
                            <td style="font-size: 0.8rem; color: #6b7280;">Subtotal Ekuitas</td>
                            <td class="text-right">Rp {{ number_format($totalEquity ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>

                <!-- TOTAL PASIVA -->
                <div class="total-box">
                    <h4>Total Liabilitas & Ekuitas</h4>
                    <span class="amount">Rp {{ number_format($totalPasiva ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>

        </div>
    </x-filament::section>
</x-filament-panels::page>
