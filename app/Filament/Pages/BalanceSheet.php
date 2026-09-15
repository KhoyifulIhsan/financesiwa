<?php

namespace App\Filament\Pages;

use App\Models\Coa;
use App\Models\JournalDetail;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class BalanceSheet extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected string $view = 'filament.pages.balance-sheet';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan Keuangan';

    protected static ?string $navigationLabel = 'Neraca';

    protected static ?string $title = 'Laporan Neraca (Balance Sheet)';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['Superadmin', 'Akuntan', 'Manajer Keuangan', 'Direktur']) ?? false;
    }

    public ?string $as_of_date = null;

    public function mount(): void
    {
        $this->as_of_date = now()->format('Y-m-d');
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->columns(1)
            ->components([
                DatePicker::make('as_of_date')
                    ->label('Per Tanggal')
                    ->live(),
            ]);
    }

    public function submit(): void
    {
        // Re-renders the component with current filter
    }

    protected function getViewData(): array
    {
        $asOfDate = $this->as_of_date;

        // 1. Aset (Debit - Kredit)
        $assetAccounts = Coa::where('type', 'asset')->orderBy('code')->get();
        $assets = [];
        $totalAsset = 0;

        foreach ($assetAccounts as $coa) {
            $query = JournalDetail::where('coa_id', $coa->id);
            if ($asOfDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $asOfDate));
            }
            $details = $query->get();
            $balance = $details->sum('debit') - $details->sum('credit');

            $assets[] = [
                'code' => $coa->code,
                'name' => $coa->name,
                'balance' => $balance,
            ];
            $totalAsset += $balance;
        }

        // 2. Liabilitas (Kredit - Debit)
        $liabilityAccounts = Coa::where('type', 'liability')->orderBy('code')->get();
        $liabilities = [];
        $totalLiability = 0;

        foreach ($liabilityAccounts as $coa) {
            $query = JournalDetail::where('coa_id', $coa->id);
            if ($asOfDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $asOfDate));
            }
            $details = $query->get();
            $balance = $details->sum('credit') - $details->sum('debit');

            $liabilities[] = [
                'code' => $coa->code,
                'name' => $coa->name,
                'balance' => $balance,
            ];
            $totalLiability += $balance;
        }

        // 3. Ekuitas (Kredit - Debit)
        $equityAccounts = Coa::where('type', 'equity')->orderBy('code')->get();
        $equities = [];
        $totalEquityWithoutEarnings = 0;

        foreach ($equityAccounts as $coa) {
            $query = JournalDetail::where('coa_id', $coa->id);
            if ($asOfDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $asOfDate));
            }
            $details = $query->get();
            $balance = $details->sum('credit') - $details->sum('debit');

            $equities[] = [
                'code' => $coa->code,
                'name' => $coa->name,
                'balance' => $balance,
            ];
            $totalEquityWithoutEarnings += $balance;
        }

        // 4. Laba / Rugi Berjalan (Pendapatan - Beban hingga asOfDate)
        $revenueAccounts = Coa::where('type', 'revenue')->get();
        $expenseAccounts = Coa::where('type', 'expense')->get();

        $currentRevenue = 0;
        foreach ($revenueAccounts as $coa) {
            $query = JournalDetail::where('coa_id', $coa->id);
            if ($asOfDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $asOfDate));
            }
            $details = $query->get();
            $currentRevenue += ($details->sum('credit') - $details->sum('debit'));
        }

        $currentExpense = 0;
        foreach ($expenseAccounts as $coa) {
            $query = JournalDetail::where('coa_id', $coa->id);
            if ($asOfDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $asOfDate));
            }
            $details = $query->get();
            $currentExpense += ($details->sum('debit') - $details->sum('credit'));
        }

        $netProfit = $currentRevenue - $currentExpense;

        // Subtotal Ekuitas di blade mencakup Ekuitas + Laba/Rugi Berjalan:
        $totalEquity = $totalEquityWithoutEarnings + $netProfit;

        // Total Pasiva = Total Liabilitas + Total Ekuitas
        $totalPasiva = $totalLiability + $totalEquity;

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equities' => $equities,
            'totalAsset' => $totalAsset,
            'totalLiability' => $totalLiability,
            'totalEquity' => $totalEquity,
            'totalPasiva' => $totalPasiva,
            'netProfit' => $netProfit,
        ];
    }
}
