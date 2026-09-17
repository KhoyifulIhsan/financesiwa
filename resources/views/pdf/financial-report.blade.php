<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Keuangan - PT Sinar Wardana Group</title>
    <style>
        @page {
            margin: 25px 35px 30px 35px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1a1a1a;
            font-size: 11px;
            line-height: 1.4;
        }
        .header-container {
            text-align: center;
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0;
            color: #111827;
        }
        .company-sub {
            font-size: 10px;
            color: #4b5563;
            margin: 2px 0 0 0;
        }
        .divider {
            border: none;
            border-top: 2px solid #111827;
            border-bottom: 1px solid #111827;
            height: 3px;
            margin: 10px 0 15px 0;
        }
        .report-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 4px 0;
        }
        .report-period {
            text-align: center;
            font-size: 10px;
            color: #4b5563;
            margin: 0 0 15px 0;
        }
        .section-header {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f3f4f6;
            padding: 6px 8px;
            border-left: 3px solid #1f2937;
            margin: 15px 0 8px 0;
        }
        .sub-header {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #374151;
            margin: 10px 0 4px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        th {
            background-color: #f9fafb;
            color: #374151;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 8px;
            border: 1px solid #d1d5db;
            text-align: left;
        }
        td {
            padding: 4.5px 8px;
            border: 1px solid #e5e7eb;
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }
        .total-row td {
            font-weight: bold;
            background-color: #f9fafb;
            border-top: 1.5px solid #9ca3af;
            border-bottom: 1.5px solid #9ca3af;
        }
        .summary-box {
            padding: 8px 12px;
            border: 1.5px solid #111827;
            background-color: #f9fafb;
            margin: 12px 0;
        }
        .summary-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .summary-amount {
            font-size: 12px;
            font-weight: bold;
            float: right;
        }
        .clear {
            clear: both;
        }
        .page-break {
            page-break-before: always;
        }
        .signature-table {
            width: 100%;
            margin-top: 30px;
            border: none;
        }
        .signature-table td {
            border: none;
            text-align: center;
            padding: 10px 20px;
            width: 33.33%;
        }
        .signature-space {
            height: 55px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
        .signature-title {
            font-size: 9px;
            color: #4b5563;
        }
        .badge-balance {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #10b981;
            color: #065f46;
            background-color: #d1fae5;
        }
        .badge-unbalanced {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #ef4444;
            color: #991b1b;
            background-color: #fee2e2;
        }
    </style>
</head>
<body>

    {{-- KOP SURAT --}}
    <div class="header-container">
        <h1 class="company-name">PT. SINAR WARDANA GROUP</h1>
        <p class="company-sub">Sistem Keuangan & Manajemen Operasional Logistik Angkutan</p>
        <p class="company-sub">Laporan Resmi Akuntansi & Finansial Perusahaan</p>
        <div class="divider"></div>
    </div>

    {{-- JUDUL LAPORAN --}}
    <div class="report-title">LAPORAN KEUANGAN LENGKAP</div>
    <div class="report-period">
        Periode: {{ $startDate ? date('d/m/Y', strtotime($startDate)) : 'Awal' }} s/d {{ $endDate ? date('d/m/Y', strtotime($endDate)) : date('d/m/Y') }}
    </div>

    {{-- ======================================================= --}}
    {{-- I. LAPORAN LABA RUGI (INCOME STATEMENT) --}}
    {{-- ======================================================= --}}
    <div class="section-header">
        I. LAPORAN LABA RUGI (INCOME STATEMENT)
    </div>

    {{-- A. PENDAPATAN --}}
    <div class="sub-header">1. Pendapatan Usaha (Revenues)</div>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Kode Akun</th>
                <th style="width: 45%;">Nama Akun</th>
                <th style="width: 30%;" class="text-right">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($revenues ?? [] as $rev)
                <tr>
                    <td class="font-mono">{{ $rev['code'] }}</td>
                    <td>{{ $rev['name'] }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($rev['balance'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="color: #6b7280; font-style: italic;">Tidak ada akun pendapatan terdaftar.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" style="text-transform: uppercase;">Total Pendapatan Usaha</td>
                <td class="text-right font-mono" style="color: #065f46;">Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- B. BEBAN --}}
    <div class="sub-header">2. Beban Operasional (Expenses)</div>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Kode Akun</th>
                <th style="width: 45%;">Nama Akun</th>
                <th style="width: 30%;" class="text-right">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses ?? [] as $exp)
                <tr>
                    <td class="font-mono">{{ $exp['code'] }}</td>
                    <td>{{ $exp['name'] }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($exp['balance'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="color: #6b7280; font-style: italic;">Tidak ada akun beban operasional terdaftar.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" style="text-transform: uppercase;">Total Beban Operasional</td>
                <td class="text-right font-mono" style="color: #991b1b;">Rp {{ number_format($totalExpense ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- LABA BERSIH BOX --}}
    <div class="summary-box">
        <span class="summary-amount font-mono" style="color: {{ ($netIncome ?? 0) >= 0 ? '#065f46' : '#991b1b' }};">
            Rp {{ number_format($netIncome ?? 0, 0, ',', '.') }}
        </span>
        <span class="summary-title">Laba / (Rugi) Bersih Periode Ini</span>
        <div class="clear"></div>
    </div>

    {{-- ======================================================= --}}
    {{-- II. LAPORAN NERACA (BALANCE SHEET) --}}
    {{-- ======================================================= --}}
    <div class="page-break"></div>

    <div class="header-container">
        <h1 class="company-name" style="font-size: 14px;">PT. SINAR WARDANA GROUP</h1>
        <div class="divider" style="margin: 6px 0 10px 0;"></div>
    </div>

    <div class="section-header">
        II. LAPORAN NERACA (POSISI KEUANGAN)
        <span style="float: right; font-weight: normal; font-size: 9.5px; text-transform: none;">
            Per {{ $endDate ? date('d/m/Y', strtotime($endDate)) : date('d/m/Y') }}
        </span>
        <div class="clear"></div>
    </div>

    {{-- 1. ASET --}}
    <div class="sub-header">1. Aset / Harta (Aktiva)</div>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Kode Akun</th>
                <th style="width: 45%;">Nama Akun</th>
                <th style="width: 30%;" class="text-right">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assets ?? [] as $asset)
                <tr>
                    <td class="font-mono">{{ $asset['code'] }}</td>
                    <td>{{ $asset['name'] }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($asset['balance'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="color: #6b7280; font-style: italic;">Tidak ada akun aset terdaftar.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" style="text-transform: uppercase;">Total Aset (Aktiva)</td>
                <td class="text-right font-mono">Rp {{ number_format($totalAsset ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- 2. LIABILITAS --}}
    <div class="sub-header">2. Liabilitas / Kewajiban (Pasiva)</div>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Kode Akun</th>
                <th style="width: 45%;">Nama Akun</th>
                <th style="width: 30%;" class="text-right">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($liabilities ?? [] as $lia)
                <tr>
                    <td class="font-mono">{{ $lia['code'] }}</td>
                    <td>{{ $lia['name'] }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($lia['balance'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="color: #6b7280; font-style: italic;">Tidak ada liabilitas berjalan.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" style="text-transform: uppercase;">Subtotal Liabilitas</td>
                <td class="text-right font-mono">Rp {{ number_format($totalLiability ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- 3. EKUITAS --}}
    <div class="sub-header">3. Ekuitas / Modal (Pasiva)</div>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Kode Akun</th>
                <th style="width: 45%;">Nama Akun</th>
                <th style="width: 30%;" class="text-right">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($equities ?? [] as $eq)
                <tr>
                    <td class="font-mono">{{ $eq['code'] }}</td>
                    <td>{{ $eq['name'] }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($eq['balance'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr style="background-color: #fafafa;">
                <td class="font-mono" style="color: #6b7280;">-</td>
                <td style="font-style: italic;">Laba / (Rugi) Berjalan Kumulatif</td>
                <td class="text-right font-mono" style="font-style: italic; color: {{ ($cumulativeNetIncome ?? 0) >= 0 ? '#065f46' : '#991b1b' }};">
                    Rp {{ number_format($cumulativeNetIncome ?? 0, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" style="text-transform: uppercase;">Subtotal Ekuitas</td>
                <td class="text-right font-mono">Rp {{ number_format($totalEquity ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- TOTAL PASIVA BOX & STATUS BALANCE --}}
    <div class="summary-box">
        <span class="summary-amount font-mono">
            Rp {{ number_format($totalPasiva ?? 0, 0, ',', '.') }}
        </span>
        <span class="summary-title">Total Liabilitas & Ekuitas (Pasiva)</span>
        <div style="margin-top: 4px; font-size: 9px;">
            Status Keseimbangan Neraca: 
            @if($isBalanced ?? false)
                <span class="badge-balance">BALANCE (SEIMBANG)</span>
            @else
                <span class="badge-unbalanced">TIDAK SEIMBANG (Selisih: Rp {{ number_format(abs(($totalAsset ?? 0) - ($totalPasiva ?? 0)), 0, ',', '.') }})</span>
            @endif
        </div>
        <div class="clear"></div>
    </div>

    {{-- LEMBAR TANDA TANGAN / PENGESAHAN --}}
    <table class="signature-table">
        <tr>
            <td>
                <div>Dibuat oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">Staff Akuntansi</div>
                <div class="signature-title">Bagian Pembukuan</div>
            </td>
            <td>
                <div>Diperiksa oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">Manajer Keuangan</div>
                <div class="signature-title">Finance & Accounting Head</div>
            </td>
            <td>
                <div>Disetujui oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">Direktur Utama</div>
                <div class="signature-title">PT Sinar Wardana Group</div>
            </td>
        </tr>
    </table>

</body>
</html>
