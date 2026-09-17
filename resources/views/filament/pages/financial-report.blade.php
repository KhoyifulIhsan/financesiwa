<x-filament-panels::page>
    {{-- 1. FILTER PERIODE FORM --}}
    <x-filament::section>
        <x-slot name="heading">
            Filter Periode Laporan
        </x-slot>
        <x-slot name="description">
            Pilih rentang tanggal untuk memfilter data Laba Rugi dan Neraca.
        </x-slot>

        <form wire:submit="submit">
            {{ $this->form }}
        </form>
    </x-filament::section>

    {{-- 2. GRID UTAMA: LABA RUGI & NERACA --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
        
        {{-- KOLOM KIRI: LAPORAN LABA RUGI --}}
        <div class="flex flex-col gap-6">
            <x-filament::section>
                <x-slot name="heading">
                    Laporan Laba Rugi (Income Statement)
                </x-slot>
                <x-slot name="description">
                    Periode: {{ $startDate ? date('d/m/Y', strtotime($startDate)) : 'Awal' }} s/d {{ $endDate ? date('d/m/Y', strtotime($endDate)) : date('d/m/Y') }}
                </x-slot>

                <div class="space-y-6">
                    {{-- PENDAPATAN --}}
                    <div>
                        <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                            Pendapatan (Revenue)
                        </h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-500 uppercase">
                                        <th class="py-2 px-3">Kode & Nama Akun</th>
                                        <th class="py-2 px-3 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @forelse($revenues ?? [] as $rev)
                                        <tr>
                                            <td class="py-2 px-3">
                                                <span class="font-mono text-gray-500 mr-2">{{ $rev['code'] }}</span>
                                                <span class="text-gray-800 dark:text-gray-200">{{ $rev['name'] }}</span>
                                            </td>
                                            <td class="py-2 px-3 text-right font-medium text-gray-900 dark:text-white tabular-nums">
                                                Rp {{ number_format($rev['balance'], 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="py-4 text-center text-gray-400">Tidak ada akun pendapatan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-gray-300 dark:border-gray-700 font-bold bg-gray-50/50 dark:bg-gray-800/50">
                                        <td class="py-2 px-3 text-gray-800 dark:text-gray-200 uppercase text-xs">Total Pendapatan</td>
                                        <td class="py-2 px-3 text-right text-emerald-600 dark:text-emerald-400 tabular-nums">
                                            Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- BEBAN OPERASIONAL --}}
                    <div>
                        <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                            Beban Operasional (Expense)
                        </h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-500 uppercase">
                                        <th class="py-2 px-3">Kode & Nama Akun</th>
                                        <th class="py-2 px-3 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @forelse($expenses ?? [] as $exp)
                                        <tr>
                                            <td class="py-2 px-3">
                                                <span class="font-mono text-gray-500 mr-2">{{ $exp['code'] }}</span>
                                                <span class="text-gray-800 dark:text-gray-200">{{ $exp['name'] }}</span>
                                            </td>
                                            <td class="py-2 px-3 text-right font-medium text-gray-900 dark:text-white tabular-nums">
                                                Rp {{ number_format($exp['balance'], 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="py-4 text-center text-gray-400">Tidak ada akun beban.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-gray-300 dark:border-gray-700 font-bold bg-gray-50/50 dark:bg-gray-800/50">
                                        <td class="py-2 px-3 text-gray-800 dark:text-gray-200 uppercase text-xs">Total Beban</td>
                                        <td class="py-2 px-3 text-right text-rose-600 dark:text-rose-400 tabular-nums">
                                            Rp {{ number_format($totalExpense ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- LABA / RUGI BERSIH BOX --}}
                    <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white text-base">Laba / (Rugi) Bersih</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Total Pendapatan - Total Beban</div>
                        </div>
                        <div class="text-lg font-bold tabular-nums {{ ($netIncome ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            Rp {{ number_format($netIncome ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- KOLOM KANAN: LAPORAN NERACA --}}
        <div class="flex flex-col gap-6">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <span>Laporan Neraca (Balance Sheet)</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ ($isBalanced ?? false) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' }}">
                            {{ ($isBalanced ?? false) ? 'Balance (Seimbang)' : 'Tidak Seimbang' }}
                        </span>
                    </div>
                </x-slot>
                <x-slot name="description">
                    Posisi per {{ $endDate ? date('d/m/Y', strtotime($endDate)) : date('d/m/Y') }}
                </x-slot>

                <div class="space-y-6">
                    {{-- 1. ASET (AKTIVA) --}}
                    <div>
                        <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                            Aset (Aktiva)
                        </h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-500 uppercase">
                                        <th class="py-2 px-3">Kode & Nama Akun</th>
                                        <th class="py-2 px-3 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @forelse($assets ?? [] as $asset)
                                        <tr>
                                            <td class="py-2 px-3">
                                                <span class="font-mono text-gray-500 mr-2">{{ $asset['code'] }}</span>
                                                <span class="text-gray-800 dark:text-gray-200">{{ $asset['name'] }}</span>
                                            </td>
                                            <td class="py-2 px-3 text-right font-medium text-gray-900 dark:text-white tabular-nums">
                                                Rp {{ number_format($asset['balance'], 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="py-4 text-center text-gray-400">Tidak ada akun aset.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-gray-300 dark:border-gray-700 font-bold bg-gray-50/50 dark:bg-gray-800/50">
                                        <td class="py-2 px-3 text-gray-800 dark:text-gray-200 uppercase text-xs">Total Aset (Aktiva)</td>
                                        <td class="py-2 px-3 text-right text-primary-600 dark:text-primary-400 tabular-nums">
                                            Rp {{ number_format($totalAsset ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- 2. LIABILITAS (KEWAJIBAN) --}}
                    <div>
                        <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                            Liabilitas (Kewajiban)
                        </h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-500 uppercase">
                                        <th class="py-2 px-3">Kode & Nama Akun</th>
                                        <th class="py-2 px-3 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @forelse($liabilities ?? [] as $lia)
                                        <tr>
                                            <td class="py-2 px-3">
                                                <span class="font-mono text-gray-500 mr-2">{{ $lia['code'] }}</span>
                                                <span class="text-gray-800 dark:text-gray-200">{{ $lia['name'] }}</span>
                                            </td>
                                            <td class="py-2 px-3 text-right font-medium text-gray-900 dark:text-white tabular-nums">
                                                Rp {{ number_format($lia['balance'], 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="py-3 text-center text-gray-400">Tidak ada liabilitas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="border-t border-gray-200 dark:border-gray-700 font-semibold bg-gray-50/30 dark:bg-gray-800/30">
                                        <td class="py-2 px-3 text-gray-600 dark:text-gray-400 text-xs">Subtotal Liabilitas</td>
                                        <td class="py-2 px-3 text-right text-gray-900 dark:text-white tabular-nums">
                                            Rp {{ number_format($totalLiability ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- 3. EKUITAS (MODAL) --}}
                    <div>
                        <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                            Ekuitas (Modal)
                        </h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-500 uppercase">
                                        <th class="py-2 px-3">Kode & Nama Akun</th>
                                        <th class="py-2 px-3 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($equities ?? [] as $eq)
                                        <tr>
                                            <td class="py-2 px-3">
                                                <span class="font-mono text-gray-500 mr-2">{{ $eq['code'] }}</span>
                                                <span class="text-gray-800 dark:text-gray-200">{{ $eq['name'] }}</span>
                                            </td>
                                            <td class="py-2 px-3 text-right font-medium text-gray-900 dark:text-white tabular-nums">
                                                Rp {{ number_format($eq['balance'], 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-gray-50/40 dark:bg-gray-800/40">
                                        <td class="py-2 px-3 italic text-gray-600 dark:text-gray-400">
                                            Laba / Rugi Berjalan (Kumulatif)
                                        </td>
                                        <td class="py-2 px-3 text-right font-semibold tabular-nums {{ ($cumulativeNetIncome ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                            Rp {{ number_format($cumulativeNetIncome ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="border-t border-gray-200 dark:border-gray-700 font-semibold bg-gray-50/30 dark:bg-gray-800/30">
                                        <td class="py-2 px-3 text-gray-600 dark:text-gray-400 text-xs">Subtotal Ekuitas</td>
                                        <td class="py-2 px-3 text-right text-gray-900 dark:text-white tabular-nums">
                                            Rp {{ number_format($totalEquity ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- TOTAL PASIVA BOX --}}
                    <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white text-base">Total Liabilitas & Ekuitas (Pasiva)</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Total Liabilitas + Total Ekuitas</div>
                        </div>
                        <div class="text-lg font-bold tabular-nums text-primary-600 dark:text-primary-400">
                            Rp {{ number_format($totalPasiva ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

    </div>
</x-filament-panels::page>
