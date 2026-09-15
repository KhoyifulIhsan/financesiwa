<?php

namespace App\Filament\Pages;

use App\Models\Coa;
use App\Models\JournalDetail;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class ProfitAndLoss extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.pages.profit-and-loss';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan Keuangan';

    protected static ?string $navigationLabel = 'Laba Rugi';

    protected static ?string $title = 'Laporan Laba Rugi (Profit & Loss)';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['Superadmin', 'Akuntan', 'Manajer Keuangan', 'Direktur']) ?? false;
    }

    public ?string $start_date = null;

    public ?string $end_date = null;

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->columns(2)
            ->components([
                DatePicker::make('start_date')
                    ->label('Dari Tanggal')
                    ->live(),
                DatePicker::make('end_date')
                    ->label('Sampai Tanggal')
                    ->live(),
            ]);
    }

    public function submit(): void
    {
        // Re-renders the component with updated filters
    }

    protected function getViewData(): array
    {
        $startDate = $this->start_date;
        $endDate = $this->end_date;

        $revenueAccounts = Coa::where('type', 'revenue')->orderBy('code')->get();
        $expenseAccounts = Coa::where('type', 'expense')->orderBy('code')->get();

        $revenues = [];
        $totalRevenue = 0;

        foreach ($revenueAccounts as $coa) {
            $query = JournalDetail::where('coa_id', $coa->id);

            if ($startDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '>=', $startDate));
            }
            if ($endDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $endDate));
            }

            $details = $query->get();
            // Saldo normal pendapatan: Kredit - Debit
            $balance = $details->sum('credit') - $details->sum('debit');

            $revenues[] = [
                'code' => $coa->code,
                'name' => $coa->name,
                'balance' => $balance,
            ];
            $totalRevenue += $balance;
        }

        $expenses = [];
        $totalExpense = 0;

        foreach ($expenseAccounts as $coa) {
            $query = JournalDetail::where('coa_id', $coa->id);

            if ($startDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '>=', $startDate));
            }
            if ($endDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $endDate));
            }

            $details = $query->get();
            // Saldo normal beban: Debit - Kredit
            $balance = $details->sum('debit') - $details->sum('credit');

            $expenses[] = [
                'code' => $coa->code,
                'name' => $coa->name,
                'balance' => $balance,
            ];
            $totalExpense += $balance;
        }

        $netProfit = $totalRevenue - $totalExpense;

        return [
            'revenues' => $revenues,
            'expenses' => $expenses,
            'totalRevenue' => $totalRevenue,
            'totalExpense' => $totalExpense,
            'netProfit' => $netProfit,
        ];
    }
}
